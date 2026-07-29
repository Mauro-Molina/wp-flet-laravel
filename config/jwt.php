<?php

return [
    'secret' => env('JWT_SECRET'),
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 900),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 2592000),
    'challenge_ttl' => (int) env('JWT_CHALLENGE_TTL', 300),
];
