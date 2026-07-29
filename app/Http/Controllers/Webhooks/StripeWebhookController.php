<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\StripeBillingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeBillingService $billing): Response
    {
        $billing->handleWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature'),
        );

        return response('OK', 200);
    }
}
