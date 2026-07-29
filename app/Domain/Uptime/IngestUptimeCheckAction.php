<?php

namespace App\Domain\Uptime;

use App\Domain\Notifications\NotificationDispatcher;
use App\Domain\Tenancy\TenantContext;
use App\Models\Incident;
use App\Models\Site;
use App\Models\UptimeCheck;

class IngestUptimeCheckAction
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function execute(Site $site, array $data): UptimeCheck
    {
        TenantContext::set($site->tenant_id);

        $check = UptimeCheck::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'is_up' => (bool) ($data['is_up'] ?? true),
            'response_time_ms' => $data['response_time_ms'] ?? null,
            'http_status' => $data['http_status'] ?? null,
            'performance' => $data['performance'] ?? null,
            'checked_at' => $data['checked_at'] ?? now(),
            'created_at' => now(),
        ]);

        if (! $check->is_up) {
            $this->openDowntimeIncident($site, $check);
        } else {
            $this->resolveOpenDowntimeIncidents($site);
        }

        return $check;
    }

    private function openDowntimeIncident(Site $site, UptimeCheck $check): void
    {
        $existing = Incident::query()
            ->where('site_id', $site->id)
            ->where('type', 'downtime')
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return;
        }

        $incident = Incident::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'type' => 'downtime',
            'status' => 'open',
            'title' => 'Site unreachable: '.$site->name,
            'description' => 'HTTP status: '.($check->http_status ?? 'unknown'),
            'started_at' => $check->checked_at,
        ]);

        $this->notifications->dispatchForTenant(
            $site->tenant_id,
            'incident.downtime',
            ['incident_id' => $incident->id, 'site_id' => $site->id],
        );
    }

    private function resolveOpenDowntimeIncidents(Site $site): void
    {
        Incident::query()
            ->where('site_id', $site->id)
            ->where('type', 'downtime')
            ->where('status', 'open')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
    }
}
