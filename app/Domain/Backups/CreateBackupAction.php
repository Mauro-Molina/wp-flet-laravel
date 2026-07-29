<?php

namespace App\Domain\Backups;

use App\Domain\Billing\SubscriptionService;
use App\Domain\Commands\CreateCommandAction;
use App\Domain\Tenancy\TenantContext;
use App\Enums\CommandType;
use App\Models\Backup;
use App\Models\Site;
use App\Models\User;

class CreateBackupAction
{
    public function __construct(
        private readonly CreateCommandAction $createCommand,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function execute(
        Site $site,
        string $idempotencyKey,
        ?string $label = null,
        ?User $creator = null,
    ): Backup {
        TenantContext::set($site->tenant_id);
        $this->subscriptions->assertBackupQuota($site);

        $command = $this->createCommand->execute(
            $site,
            CommandType::BackupCreate->value,
            $idempotencyKey,
            ['label' => $label],
            $creator,
        );

        return Backup::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'command_id' => $command->id,
            'type' => 'on_demand',
            'status' => 'pending',
            'label' => $label,
        ]);
    }
}
