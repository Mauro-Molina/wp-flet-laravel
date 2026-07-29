<?php

return [
    'timeout_seconds' => (int) env('CONTENT_PROXY_TIMEOUT', 30),

    /** Path segment after site base URL where the plugin exposes WP REST. */
    'agent_path_prefix' => env('CONTENT_AGENT_PATH_PREFIX', 'wp-json/wp/v2'),
];
