<?php

namespace App\Domain\Updates;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use App\Models\SitePendingUpdate;

class SyncPendingUpdatesAction
{
    /**
     * @param  list<array<string, mixed>>  $updates
     */
    public function execute(Site $site, array $updates): int
    {
        TenantContext::set($site->tenant_id);
        $count = 0;

        foreach ($updates as $update) {
            SitePendingUpdate::query()->updateOrCreate(
                [
                    'site_id' => $site->id,
                    'update_type' => $update['update_type'],
                    'item_slug' => $update['item_slug'],
                ],
                [
                    'tenant_id' => $site->tenant_id,
                    'item_name' => $update['item_name'] ?? null,
                    'current_version' => $update['current_version'] ?? null,
                    'available_version' => $update['available_version'] ?? null,
                    'metadata' => $update['metadata'] ?? null,
                    'detected_at' => $update['detected_at'] ?? now(),
                ],
            );
            $count++;
        }

        return $count;
    }
}
