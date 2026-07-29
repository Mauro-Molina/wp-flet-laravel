<?php

namespace App\Domain\Events;

use App\Domain\Tenancy\TenantContext;
use App\Models\EventLog;
use App\Models\Site;
use Illuminate\Support\Str;

class IngestEventsAction
{
    public function __construct(private readonly EventBusInterface $eventBus) {}

    /**
     * @param  list<array{event_type: string, payload?: array|null, occurred_at?: string|null}>  $events
     * @return list<EventLog>
     */
    public function execute(Site $site, array $events): array
    {
        TenantContext::set($site->tenant_id);
        $stored = [];

        foreach ($events as $event) {
            $log = EventLog::query()->create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
                'event_type' => $event['event_type'],
                'payload' => $event['payload'] ?? null,
                'occurred_at' => isset($event['occurred_at'])
                    ? \Carbon\Carbon::parse($event['occurred_at'])
                    : now(),
                'created_at' => now(),
            ]);

            $this->eventBus->dispatch(
                $log->event_type,
                $log->payload ?? [],
                $site->tenant_id,
                $site->id,
            );

            $stored[] = $log;
        }

        return $stored;
    }
}
