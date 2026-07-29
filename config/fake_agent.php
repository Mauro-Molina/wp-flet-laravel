<?php

return [
    'enabled' => filter_var(env('FAKE_AGENT_ENABLED', false), FILTER_VALIDATE_BOOL),

    /** Base URL of this Laravel app (or dedicated stub host) when proxying in dev. */
    'base_url' => rtrim(env('FAKE_AGENT_BASE_URL', env('APP_URL', 'http://localhost')), '/'),

    'route_prefix' => 'fake-agent/wp/v2',
];
