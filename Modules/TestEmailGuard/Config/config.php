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
    | Optional real mailbox to plus-address rewritten mail into, e.g.
    | armssink@threls.onmicrosoft.com. When empty, rewrites target
    | example.com and sends will bounce.
    |
    */

    'sink' => env('TEST_EMAIL_GUARD_SINK'),

];
