<?php

namespace App\Domain\Events;

interface EventBusInterface
{
    /**
     * Dispatch a domain event to the internal bus (queue abstraction).
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $eventType, array $payload, string $tenantId, string $siteId): void;
}
