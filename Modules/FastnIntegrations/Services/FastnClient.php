<?php

namespace Modules\FastnIntegrations\Services;

use App\Conversation;
use App\Option;

/**
 * Talks to the fastn API from the FreeScout server.
 *
 * - Mints embed tokens for the Integrations page (the API key never reaches the browser).
 * - Runs the ticket workflows (Linear issue, HubSpot contact, Google Sheets row)
 *   for this FreeScout install, which is one fastn customer.
 */
class FastnClient
{
    /**
     * Set while FreeScout applies a change that came FROM an app (two-way),
     * so that change is not sent straight back out.
     */
    public static $suppress = false;

    /**
     * What it does => workflow id or slug, from config (.env).
     */
    public static function workflows()
    {
        return array_filter(array_map('trim', (array) config('fastnintegrations.workflows', [])));
    }

    public static function apiUrl()
    {
        return rtrim(config('fastnintegrations.api_url', 'https://api.fastn.dev'), '/');
    }

    public static function apiKey()
    {
        return trim((string) config('fastnintegrations.api_key', ''));
    }

    public static function isConfigured()
    {
        return self::apiKey() !== '';
    }

    /**
     * This FreeScout install as a fastn customer: a stable reference made from the app URL,
     * unless the admin set one.
     */
    public static function customerRef()
    {
        $ref = trim((string) config('fastnintegrations.customer_ref', ''));
        if ($ref !== '') {
            return $ref;
        }

        return 'freescout-'.substr(sha1((string) config('app.url')), 0, 12);
    }

    public static function customerName()
    {
        $name = trim((string) config('fastnintegrations.customer_name', ''));

        return $name !== '' ? $name : (string) Option::get('company_name', config('app.name'));
    }

    /**
     * Mint an embed token for the signed-in FreeScout user.
     * Registers the customer on first use (createIfMissing).
     */
    public static function mintEmbedToken($user)
    {
        $body = [
            'endOrgId'        => self::customerRef(),
            'createIfMissing' => true,
            'customerName'    => self::customerName(),
        ];
        if ($user) {
            $body['userId'] = 'freescout-user-'.$user->id;
            $body['userEmail'] = $user->email;
            $body['userName'] = $user->getFullName();
        }

        $res = self::request('POST', '/api/v1/embed/token', $body);
        if ($res['status'] >= 200 && $res['status'] < 300 && !empty($res['json']['data']['token'])) {
            if (!empty($res['json']['data']['endOrgId'])) {
                Option::set('fastn_end_org_id', $res['json']['data']['endOrgId']);
            }

            return ['ok' => true, 'token' => $res['json']['data']['token'], 'expiresAt' => $res['json']['data']['expiresAt'] ?? null];
        }

        return ['ok' => false, 'error' => self::errorText($res)];
    }

    /**
     * Run one workflow for this FreeScout customer.
     */
    /**
     * fastn's own id for this help desk. Learned when the first embed token is minted
     * (which also registers the help desk as a fastn customer).
     */
    public static function endOrgId()
    {
        $id = (string) Option::get('fastn_end_org_id', '');
        if ($id === '') {
            self::mintEmbedToken(null);
            $id = (string) Option::get('fastn_end_org_id', '');
        }

        return $id;
    }

    /**
     * Workflow slug/id => installation id, for every integration this help desk has
     * switched on in the Integrations page. A workflow with no installation means the
     * help desk has not connected that app, so it is skipped. Cached for a minute.
     */
    public static function installationsByWorkflow()
    {
        $endOrgId = self::endOrgId();
        if ($endOrgId === '') {
            return [];
        }

        return \Cache::remember('fastn_installations_'.$endOrgId, 5, function () use ($endOrgId) {
            $inst = self::request('GET', '/api/v1/installations?end_org_id='.rawurlencode($endOrgId));
            $widgets = self::request('GET', '/api/v1/widgets');
            if ($inst['status'] !== 200 || $widgets['status'] !== 200) {
                throw new \Exception('fastn lookup failed: '.self::errorText($inst['status'] !== 200 ? $inst : $widgets));
            }

            $workflowsByWidget = [];
            foreach ((array) ($widgets['json']['data'] ?? []) as $w) {
                foreach ((array) ($w['workflowRefs'] ?? []) as $ref) {
                    $workflowsByWidget[$w['id']][] = $ref['slug'] ?? '';
                    $workflowsByWidget[$w['id']][] = $ref['workflowId'] ?? '';
                }
            }

            $map = [];
            foreach ((array) ($inst['json']['data'] ?? []) as $row) {
                $status = $row['status'] ?? '';
                $widgetId = $row['widgetId'] ?? ($row['widget_id'] ?? '');
                if ($status !== 'active' || !$widgetId || empty($workflowsByWidget[$widgetId])) {
                    continue;
                }
                foreach ($workflowsByWidget[$widgetId] as $wf) {
                    if ($wf !== '') {
                        $map[$wf] = $row['id'];
                    }
                }
            }

            return $map;
        });
    }

    public static function executeWorkflow($workflowId, array $input, $idempotencyKey = null, $installationId = null)
    {
        // The tenant (this help desk) is always named; the installation then picks
        // that help desk's own connection and its settings.
        $headers = ['x-end-org-ref: '.self::customerRef()];
        if ($installationId) {
            $headers[] = 'x-installation-id: '.$installationId;
        }
        if ($idempotencyKey) {
            $headers[] = 'Idempotency-Key: '.$idempotencyKey;
        }

        $res = self::request('POST', '/api/v1/workflows/'.rawurlencode($workflowId).'/execute', ['input' => $input], $headers);

        // The help desk reconnected an app: its old installation is gone. Forget the
        // saved list, look the installation up again and retry once.
        if ($installationId && in_array($res['status'], [400, 404]) && stripos(self::errorText($res), 'Installation') !== false) {
            \Cache::forget('fastn_installations_'.self::endOrgId());
            $fresh = self::installationsByWorkflow()[$workflowId] ?? null;
            if ($fresh && $fresh !== $installationId) {
                return self::executeWorkflow($workflowId, $input, $idempotencyKey, $fresh);
            }
        }

        $httpOk = $res['status'] >= 200 && $res['status'] < 300;
        $error = $httpOk ? null : self::errorText($res);

        // A 200 can still carry a failure inside the workflow's own result
        // (e.g. HubSpot refused the contact). Report it as a failure too.
        $data = $res['json']['data'] ?? [];
        if ($httpOk && !empty($data['errors'])) {
            $first = $data['errorDetails'][0]['errorMessage'] ?? 'the app reported an error';
            $error = mb_substr((string) $first, 0, 300);
        }

        return [
            'ok'          => $error === null,
            'status'      => $res['status'],
            'executionId' => $res['json']['executionId'] ?? ($data['executionId'] ?? ($res['headers']['x-execution-id'] ?? null)),
            'result'      => $res['json'],
            'error'       => $error,
        ];
    }

    /**
     * Send a new ticket to every configured workflow. Never throws: a fastn
     * problem must not break ticket intake. Problems go to the FreeScout app log.
     */
    public static function sendNewTicket(Conversation $conversation)
    {
        if (!self::isConfigured() || !Option::get('fastn_send_new_tickets', true)) {
            return [];
        }

        $input = TicketPayload::build($conversation);

        return self::sendToAll($input, 'ticket:'.$conversation->id, '#'.$conversation->number);
    }

    /**
     * Send a ticket update (status change, reply, note, assignment) to every
     * connected app. Runs in FreeScout's background queue. Never throws.
     */
    public static function sendTicketEvent($conversationId, $event, array $change = [], $dedupe = null)
    {
        if (!self::isConfigured() || !Option::get('fastn_send_new_tickets', true)) {
            return [];
        }
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return [];
        }
        $input = TicketPayload::build($conversation, $event, $change);
        $key = 'ticket:'.$conversation->id.':'.$event.':'.($dedupe ?: md5(json_encode($change).microtime()));

        return self::sendToAll($input, $key, '#'.$conversation->number.' '.$event);
    }

    /**
     * Two-way: ask fastn to check the Linear issues linked to this help desk's
     * tickets. Changes come back to /fastn-integrations/events. Runs every
     * minute from FreeScout's scheduler. Does nothing until Linear is connected.
     */
    public static function runLinearCheck()
    {
        $checker = (string) config('fastnintegrations.linear_check');
        $linearFlow = self::workflows()['Linear issue'] ?? '';
        if (!$checker || !$linearFlow || !self::isConfigured()) {
            return null;
        }
        try {
            $installationId = self::installationsByWorkflow()[$linearFlow] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
        if (!$installationId) {
            return null;
        }
        $res = self::executeWorkflow($checker, [], null, $installationId);
        if (!$res['ok']) {
            \Helper::log('fastn', 'Linear check failed: '.$res['error']);
        }

        return $res;
    }

    /**
     * Run every configured workflow whose app this help desk has switched on.
     */
    public static function sendToAll(array $input, $dedupeKey = null, $label = '')
    {
        $results = [];
        try {
            $installations = self::installationsByWorkflow();
        } catch (\Throwable $e) {
            \Helper::log('fastn', 'Ticket '.$label.': '.$e->getMessage());

            return ['fastn' => ['ok' => false, 'error' => $e->getMessage()]];
        }

        foreach (self::workflows() as $name => $workflowId) {
            if (empty($installations[$workflowId])) {
                $results[$name] = ['ok' => true, 'skipped' => true, 'error' => null, 'reason' => 'not switched on'];
                continue;
            }
            try {
                $key = $dedupeKey ? 'freescout:'.self::customerRef().':'.$dedupeKey.':'.$workflowId : null;
                $results[$name] = self::executeWorkflow($workflowId, $input, $key, $installations[$workflowId]);
                if (!$results[$name]['ok']) {
                    \Helper::log('fastn', 'Ticket '.$label.' -> '.$name.' failed: '.$results[$name]['error']);
                }
            } catch (\Throwable $e) {
                $results[$name] = ['ok' => false, 'error' => $e->getMessage()];
                \Helper::log('fastn', 'Ticket '.$label.' -> '.$name.' failed: '.$e->getMessage());
            }
        }

        return $results;
    }

    protected static function request($method, $path, $body = null, array $extraHeaders = [])
    {
        $key = self::apiKey();
        $headers = array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$key,
        ], $extraHeaders);
        // Test keys must say so on every call.
        if (strpos($key, 'fsk_test_') === 0) {
            $headers[] = 'X-fastn-Test-Mode: true';
        }

        $responseHeaders = [];
        $ch = curl_init(self::apiUrl().$path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => (int) config('fastnintegrations.timeout', 10),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseHeaders) {
                $parts = explode(':', $line, 2);
                if (count($parts) == 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        \Helper::setCurlDefaultOptions($ch);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'status'    => $status,
            'raw'       => $raw,
            'json'      => is_string($raw) ? json_decode($raw, true) : null,
            'headers'   => $responseHeaders,
            'curlError' => $curlError,
        ];
    }

    protected static function errorText($res)
    {
        if (!empty($res['curlError'])) {
            return $res['curlError'];
        }
        $json = $res['json'];
        $msg = $json['error']['message'] ?? $json['message'] ?? $json['error'] ?? null;
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $code = $json['error']['code'] ?? $json['code'] ?? '';

        return 'HTTP '.$res['status'].($code ? ' '.$code : '').($msg ? ': '.$msg : '');
    }
}
