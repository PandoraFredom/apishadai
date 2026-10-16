<?php

return [
    'url' => env('HQ_API_URL'),
    'key_id' => env('HQ_API_KEY_ID', 'apishadai'),
    'secret' => env('HQ_API_SIGNING_SECRET'),
    'publishing_enabled' => env('HQ_PUBLISHING_ENABLED', true),
    'send_immediately' => env('HQ_SEND_IMMEDIATELY', true),
    // Approval goes only to this server-controlled address, never one supplied by the client.
    'approval_email' => env('HEADQUARTERS_APPROVAL_EMAIL'),
    'pos_sender_ips' => array_filter(array_map('trim', explode(',', env('HQ_POS_ALLOWED_IPS', '127.0.0.1,::1')))),
    'registration_minutes' => 10,
    'max_code_attempts' => 5,
];
