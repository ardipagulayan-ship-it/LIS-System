<?php

return [
    'enabled'       => env('ULM_ENABLED', false),
    'endpoint'      => env('ULM_ENDPOINT', ''),
    'endpoint_path' => env('ULM_ENDPOINT_PATH', '/orders/lis'),
    'token'         => env('ULM_TOKEN', ''),
    'timeout'       => (int) env('ULM_TIMEOUT', 15),
    'log_channel'   => env('ULM_LOG_CHANNEL', 'ulm'),
];