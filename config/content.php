<?php

return [
    'timeout_seconds' => (int) env('CONTENT_PROXY_TIMEOUT', 30),

    /**
     * Path after site base URL where the WP Fleet plugin exposes content REST.
     * Real plugin: wp-json/wpfleet/v1 — not core wp/v2.
     */
    'agent_path_prefix' => env('CONTENT_AGENT_PATH_PREFIX', 'wp-json/wpfleet/v1'),
];
