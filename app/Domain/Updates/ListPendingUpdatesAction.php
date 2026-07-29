<?php

namespace App\Domain\Updates;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use App\Models\SitePendingUpdate;
use Illuminate\Support\Collection;

class ListPendingUpdatesAction
{
    /**
     * @return Collection<int, SitePendingUpdate>
     */
    public function execute(Site $site, ?string $updateType = null): Collection
    {
        TenantContext::set($site->tenant_id);

        $query = SitePendingUpdate::query()->where('site_id', $site->id)->orderByDesc('detected_at');

        if ($updateType !== null) {
            $query->where('update_type', $updateType);
        }

        return $query->get();
    }
}
