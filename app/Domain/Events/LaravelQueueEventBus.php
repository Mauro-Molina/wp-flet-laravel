<?php

namespace App\Domain\Events;

use App\Jobs\DispatchDomainEventJob;

/**
 * Laravel queue implementation. Swap for SQS/RabbitMQ by binding EventBusInterface elsewhere.
 */
class LaravelQueueEventBus implements EventBusInterface
{
    public function dispatch(string $eventType, array $payload, string $tenantId, string $siteId): void
    {
        DispatchDomainEventJob::dispatch($eventType, $payload, $tenantId, $siteId);
    }
}
