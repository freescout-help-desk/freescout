<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow-listed recipient domains
    |--------------------------------------------------------------------------
    |
    | Comma-separated domains whose recipients receive real mail, untouched
    | by the guard. Exact-domain match. Defaults to arms.com.mt,threls.com
    | when unset (the default is applied in EmailAnonymizer, not here, so a
    | partially-cached config cannot blank it out).
    |
    */

    'allow_domains' => env('TEST_EMAIL_GUARD_ALLOW_DOMAINS'),

    /*
    |--------------------------------------------------------------------------
    | Sink mailbox
    |--------------------------------------------------------------------------
    |
    | Optional real mailbox rewritten mail is delivered into, e.g.
    | armssink@threls.onmicrosoft.com. When empty, rewrites target
    | example.com and sends will bounce.
    |
    */

    'sink' => env('TEST_EMAIL_GUARD_SINK'),

    /*
    |--------------------------------------------------------------------------
    | Sink recipient mode
    |--------------------------------------------------------------------------
    |
    | "plain" (default): rewritten mail is addressed to the bare sink
    | address; the original recipient is carried in the display name and
    | an X-Original-To header. Works on any mail host.
    |
    | "plus": rewritten mail is plus-addressed into the sink
    | (armssink+local+domain@...). Requires the sink's mail host to accept
    | plus addressing (Exchange Online: DisallowPlusAddressInRecipients
    | must be false) - probe with a manual send to sink+test@... before
    | relying on it.
    |
    */

    'sink_mode' => env('TEST_EMAIL_GUARD_SINK_MODE'),

];
