<?php

namespace Modules\FastnIntegrations\Http\Controllers;

use App\Conversation;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FastnIntegrations\Services\FastnClient;

/**
 * Receives changes made in the connected apps and applies them to the ticket.
 *
 * Today: a Linear issue linked to a ticket changes state.
 *   Done / Canceled          -> ticket Closed
 *   any open state, ticket closed -> ticket reopened (Active)
 * Every change also leaves an internal note on the ticket.
 *
 * fastn signs each request: X-Fastn-Signature = sha256=HMAC(secret, "<X-Fastn-Timestamp>.<raw body>").
 */
class FastnEventsController extends Controller
{
    public function receive(Request $request)
    {
        $secret = (string) config('fastnintegrations.webhook_secret');
        $raw = (string) $request->getContent();
        $ts = (string) $request->header('X-Fastn-Timestamp');
        $sig = (string) $request->header('X-Fastn-Signature');

        if ($secret === '' || $ts === '' || $sig === '') {
            return response()->json(['error' => 'missing signature'], 401);
        }
        $expected = 'sha256='.hash_hmac('sha256', $ts.'.'.$raw, $secret);
        if (!hash_equals($expected, $sig)) {
            return response()->json(['error' => 'bad signature'], 401);
        }
        if (abs(time() - (int) $ts) > 86400 && abs(time() - (int) ($ts / 1000)) > 86400) {
            return response()->json(['error' => 'stale request'], 401);
        }

        // fastn retries; the event id never changes, so apply each one once.
        $eventId = (string) $request->header('X-Fastn-Event-Id');
        if ($eventId !== '' && !\Cache::add('fastn_evt_'.$eventId, 1, 60 * 24)) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $body = json_decode($raw, true) ?: [];
        if (($body['event'] ?? '') === 'endpoint.test' || ($body['type'] ?? '') === 'endpoint.test') {
            return response()->json(['ok' => true, 'test' => true]);
        }

        $conversation = Conversation::find((int) ($body['ticketId'] ?? 0));
        if (!$conversation) {
            return response()->json(['ok' => true, 'skipped' => 'ticket not found']);
        }

        $stateType = strtolower((string) ($body['stateType'] ?? ''));
        $stateName = (string) ($body['stateName'] ?? $stateType);
        $issueKey = (string) ($body['issueIdentifier'] ?? 'issue');
        $issueUrl = (string) ($body['issueUrl'] ?? '');

        $newStatus = null;
        if (in_array($stateType, ['completed', 'canceled'])) {
            $newStatus = Conversation::STATUS_CLOSED;
        } elseif ($conversation->status == Conversation::STATUS_CLOSED) {
            $newStatus = Conversation::STATUS_ACTIVE;
        }

        $actor = User::where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        $changed = false;

        FastnClient::$suppress = true;
        try {
            if ($newStatus && $conversation->status != $newStatus) {
                $conversation->changeStatus($newStatus, $actor);
                $changed = true;
            }

            $link = $issueUrl ? ' (<a href="'.e($issueUrl).'" target="_blank">'.e($issueKey).'</a>)' : ' ('.e($issueKey).')';
            $note = 'Linear issue'.$link.' moved to <b>'.e($stateName).'</b>.';
            if ($changed) {
                $note .= ' Ticket '.($newStatus == Conversation::STATUS_CLOSED ? 'closed' : 'reopened').' automatically.';
            }
            Thread::create($conversation, Thread::TYPE_NOTE, $note, [
                'user_id'            => $conversation->user_id,
                'created_by_user_id' => $actor ? $actor->id : null,
                'source_via'         => Thread::PERSON_USER,
                'source_type'        => Thread::SOURCE_TYPE_API,
            ]);
            $conversation->touch();
        } finally {
            FastnClient::$suppress = false;
        }

        // Tell the other apps (e.g. the Sheets row) the status changed. The Linear
        // flow sees the issue is already in that state and does nothing, so no loop.
        if ($changed && FastnClient::isConfigured()) {
            $prev = $newStatus == Conversation::STATUS_CLOSED ? 'Active' : 'Closed';
            \Helper::backgroundAction('fastnintegrations.ticket_event', [
                (int) $conversation->id, 'ticket.status_changed',
                ['by' => 'Linear ('.$issueKey.')', 'prevStatus' => $prev],
                'linear:'.$eventId,
            ]);
        }

        return response()->json([
            'ok'      => true,
            'ticket'  => $conversation->number,
            'status'  => $conversation->getStatusName(),
            'changed' => $changed,
        ]);
    }
}
