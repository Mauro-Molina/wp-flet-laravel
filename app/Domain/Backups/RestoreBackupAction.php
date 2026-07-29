<?php

namespace App\Domain\Backups;

use App\Domain\Commands\CreateCommandAction;
use App\Domain\Tenancy\TenantContext;
use App\Enums\CommandType;
use App\Models\Backup;
use App\Models\Command;
use App\Models\Site;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RestoreBackupAction
{
    public function __construct(private readonly CreateCommandAction $createCommand) {}

    public function execute(
        Site $site,
        Backup $backup,
        string $idempotencyKey,
        string $siteNameConfirmation,
        bool $confirmedDestructive,
        ?User $creator = null,
    ): Command {
        TenantContext::set($site->tenant_id);

        if ($backup->site_id !== $site->id) {
            abort(404);
        }

        if (! $confirmedDestructive) {
            throw ValidationException::withMessages([
                'confirmed_destructive' => ['Destructive restore requires explicit confirmation.'],
            ]);
        }

        if ($siteNameConfirmation !== $site->name) {
            throw ValidationException::withMessages([
                'site_name_confirmation' => ['Site name confirmation does not match.'],
            ]);
        }

        return $this->createCommand->execute(
            $site,
            CommandType::BackupRestore->value,
            $idempotencyKey,
            [
                'backup_id' => $backup->id,
                'site_name_confirmation' => $siteNameConfirmation,
                'confirmed_destructive' => true,
            ],
            $creator,
        );
    }
}
