<?php

return [
    'stripe_secret' => env('STRIPE_SECRET'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),
];
