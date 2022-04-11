<?php

return [
    'name' => 'Noreply',
    'emails_custom' => env('NOREPLY_EMAILS_CUSTOM', '[]'),
    'emails_default' => ["no-reply", "do-not-reply", "ne-pas-repondre", "no-responder", "auto-responder", "auto-reply", ],
];
