<?php

namespace App\Domain\Backups;

use App\Domain\Tenancy\TenantContext;
use App\Models\Backup;

class CompleteBackupAction
{
    public function execute(Backup $backup, ?int $sizeBytes = null, ?string $storagePath = null): Backup
    {
        TenantContext::set($backup->tenant_id);

        $backup->forceFill([
            'status' => 'completed',
            'size_bytes' => $sizeBytes,
            'storage_path' => $storagePath,
            'completed_at' => now(),
        ])->save();

        return $backup->refresh();
    }
}
