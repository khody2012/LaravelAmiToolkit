<?php

return [
    'host' => env('AMI_HOST', '127.0.0.1'),
    'port' => env('AMI_PORT', 5038),
    'username' => env('AMI_USERNAME', 'Hello'),
    'secret' => env('AMI_SECRET', 'Hello'),
    'timeout' => env('AMI_TIMEOUT', 30),
    'broadcast' => env('AMI_BROADCAST', true),
];