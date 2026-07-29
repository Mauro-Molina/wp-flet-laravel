<?php

return [
  'algorithm' => 'sha256',
  'replay_window_seconds' => (int) env('HMAC_REPLAY_WINDOW', 300),
  'command_ttl_seconds' => (int) env('COMMAND_TTL_SECONDS', 86400),
];
