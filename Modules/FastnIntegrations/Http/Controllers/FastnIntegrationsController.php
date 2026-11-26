<?php

namespace Modules\FastnIntegrations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FastnIntegrations\Services\FastnClient;
use Modules\FastnIntegrations\Services\TicketPayload;

class FastnIntegrationsController extends Controller
{
    public function token(Request $request)
    {
        if (!FastnClient::isConfigured()) {
            return response()->json(['error' => __('fastn is not set up on this server (FASTN_API_KEY is missing).')], 400);
        }

        $res = FastnClient::mintEmbedToken(auth()->user());
        if (!$res['ok']) {
            \Helper::log('fastn', 'Embed token failed: '.$res['error']);

            return response()->json(['error' => $res['error']], 502);
        }

        return response()->json(['token' => $res['token'], 'expiresAt' => $res['expiresAt']]);
    }

    public function test(Request $request)
    {
        if (!FastnClient::isConfigured()) {
            return response()->json(['error' => __('fastn is not set up on this server (FASTN_API_KEY is missing).')], 400);
        }

        // Same path as a real new ticket, without de-duplication so it can be sent again.
        \Cache::forget('fastn_installations_'.FastnClient::endOrgId());
        $results = FastnClient::sendToAll(TicketPayload::sample(), null, 'test');

        return response()->json(['results' => $results]);
    }
}
