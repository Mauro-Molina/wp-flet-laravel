<?php

namespace App\Domain\Commands;

use App\Enums\CommandStatus;
use App\Domain\Tenancy\TenantContext;
use App\Models\Command;
use App\Models\Site;
use Illuminate\Support\Collection;

class ListPendingCommandsAction
{
    public function __construct(private readonly ExpireStaleCommandsAction $expireStale) {}

    /**
     * @return Collection<int, Command>
     */
    public function execute(Site $site): Collection
    {
        TenantContext::set($site->tenant_id);
        $this->expireStale->execute($site->id);

        return Command::query()
            ->where('site_id', $site->id)
            ->where('status', CommandStatus::Pending)
            ->orderBy('created_at')
            ->get();
    }
}
