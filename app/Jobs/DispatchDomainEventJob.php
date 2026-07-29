<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Internal bus stub — processors subscribe here in later phases.
 */
class DispatchDomainEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly array $payload,
        public readonly string $tenantId,
        public readonly string $siteId,
    ) {}

    public function handle(): void
    {
        Log::debug('domain_event', [
            'event_type' => $this->eventType,
            'tenant_id' => $this->tenantId,
            'site_id' => $this->siteId,
            'payload' => $this->payload,
        ]);
    }
}
