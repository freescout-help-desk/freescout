<?php

// All fastn settings are server-side (.env). None of them is shown in the UI.
return [
    'name'          => 'FastnIntegrations',
    // fastn API host. The embed script and the token API are served from it.
    'api_url'       => env('FASTN_API_URL', 'https://api.fastn.dev'),
    // The FreeScout operator's fastn API key. Never sent to the browser.
    'api_key'       => env('FASTN_API_KEY', ''),
    // How this help desk is known in fastn (one fastn customer per FreeScout install).
    // Empty: derived from APP_URL.
    'customer_ref'  => env('FASTN_CUSTOMER_REF', ''),
    'customer_name' => env('FASTN_CUSTOMER_NAME', ''),
    // Workflows each new ticket is sent to (id or slug). Empty turns one off.
    'workflows'     => [
        'Linear issue'      => env('FASTN_WF_LINEAR', 'freescout-ticket-to-linear-issue'),
        'HubSpot contact'   => env('FASTN_WF_HUBSPOT', 'freescout-ticket-to-hubspot-contact'),
        'Google Sheets row' => env('FASTN_WF_SHEETS', 'freescout-ticket-to-google-sheets-row'),
    ],
    // Two-way: checks the Linear issues linked to tickets and sends state changes back.
    'linear_check'  => env('FASTN_WF_LINEAR_CHECK', 'linear-check-freescout-tickets'),
    // Seconds to wait for each fastn call when a ticket comes in.
    'timeout'       => (int) env('FASTN_TIMEOUT', 10),
    // Signing secret of the fastn event subscription that calls /fastn-integrations/events.
    'webhook_secret' => env('FASTN_WEBHOOK_SECRET', ''),
];
