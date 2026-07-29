<?php

namespace App\Domain\Notifications;

use App\Domain\Tenancy\TenantContext;
use App\Jobs\DispatchNotificationJob;
use App\Models\NotificationHistory;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationDispatcher
{
    public function dispatchForTenant(string $tenantId, string $eventType, array $payload): void
    {
        TenantContext::set($tenantId);

        $users = User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->get();

        foreach ($users as $user) {
            $this->dispatchForUser($user, $eventType, $payload);
        }
    }

    public function dispatchForUser(User $user, string $eventType, array $payload): void
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            return;
        }

        $preferences = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->where('enabled', true)
            ->get();

        if ($preferences->isEmpty()) {
            $preferences = collect([
                (object) ['channel' => 'in_app'],
            ]);
        }

        foreach ($preferences as $pref) {
            $channel = is_object($pref) && isset($pref->channel) ? $pref->channel : $pref['channel'] ?? 'in_app';

            $history = NotificationHistory::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'event_type' => $eventType,
                'channel' => $channel,
                'status' => 'pending',
                'payload' => $payload,
            ]);

            DispatchNotificationJob::dispatch($history->id);
        }
    }
}
