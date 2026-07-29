<?php

namespace App\Domain\Billing;

use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeBillingService
{
    private ?StripeClient $stripe = null;

    public function client(): StripeClient
    {
        if ($this->stripe === null) {
            $this->stripe = new StripeClient(config('billing.stripe_secret'));
        }

        return $this->stripe;
    }

    public function isConfigured(): bool
    {
        return ! empty(config('billing.stripe_secret'));
    }

    /**
     * @return array{customer_id: string, subscription_id: string}
     */
    public function createTestSubscription(
        TenantSubscription $subscription,
        string $email,
        string $paymentMethodId,
    ): array {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured.');
        }

        $customer = $this->client()->customers->create([
            'email' => $email,
            'payment_method' => $paymentMethodId,
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);

        $stripeSub = $this->client()->subscriptions->create([
            'customer' => $customer->id,
            'items' => [['price' => $subscription->plan->stripe_price_id]],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $subscription->forceFill([
            'stripe_customer_id' => $customer->id,
            'stripe_subscription_id' => $stripeSub->id,
            'status' => $stripeSub->status,
            'current_period_start' => now()->createFromTimestamp($stripeSub->current_period_start),
            'current_period_end' => now()->createFromTimestamp($stripeSub->current_period_end),
        ])->save();

        return [
            'customer_id' => $customer->id,
            'subscription_id' => $stripeSub->id,
        ];
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        if (! $this->isConfigured()) {
            Log::warning('Stripe webhook received but Stripe is not configured.');

            return;
        }

        $secret = config('billing.stripe_webhook_secret');

        if ($secret && $signature) {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } else {
            $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        }

        match ($event->type ?? null) {
            'invoice.payment_failed' => $this->handlePaymentFailed($event),
            'invoice.paid', 'customer.subscription.updated' => $this->handleSubscriptionRenewed($event),
            'customer.subscription.deleted' => $this->handleSubscriptionCanceled($event),
            default => Log::debug('Unhandled Stripe event', ['type' => $event->type ?? 'unknown']),
        };
    }

    private function handlePaymentFailed(object $event): void
    {
        $subscriptionId = $event->data->object->subscription ?? null;
        if ($subscriptionId === null) {
            return;
        }

        $subscription = TenantSubscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->forceFill([
            'status' => 'past_due',
            'grace_ends_at' => now()->addDays((int) config('billing.grace_days', 7)),
        ])->save();

        app(\App\Domain\Licensing\LicenseSyncService::class)
            ->syncTenantSitesToGrace($subscription->tenant_id, $subscription->grace_ends_at);
    }

    private function handleSubscriptionRenewed(object $event): void
    {
        $object = $event->data->object;
        $subscriptionId = $object->subscription ?? $object->id ?? null;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = TenantSubscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->forceFill([
            'status' => 'active',
            'grace_ends_at' => null,
            'commands_used_this_period' => 0,
            'current_period_start' => isset($object->current_period_start)
                ? now()->createFromTimestamp($object->current_period_start) : now(),
            'current_period_end' => isset($object->current_period_end)
                ? now()->createFromTimestamp($object->current_period_end) : now()->addMonth(),
        ])->save();

        app(\App\Domain\Licensing\LicenseSyncService::class)
            ->activateAllTenantSites($subscription->tenant_id);
    }

    private function handleSubscriptionCanceled(object $event): void
    {
        $subscriptionId = $event->data->object->id ?? null;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = TenantSubscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->forceFill(['status' => 'canceled'])->save();

        app(\App\Domain\Licensing\LicenseSyncService::class)
            ->suspendAllTenantSites($subscription->tenant_id);
    }
}
