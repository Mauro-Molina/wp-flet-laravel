<?php

namespace App\Domain\Commands;

use App\Enums\CommandStatus;
use App\Domain\Tenancy\TenantContext;
use App\Models\Command;

class FailCommandAction
{
    public function execute(Command $command, ?string $errorMessage = null, ?array $result = null): Command
    {
        TenantContext::set($command->tenant_id);

        if (! $command->isPending()) {
            return $command;
        }

        $command->forceFill([
            'status' => CommandStatus::Failed,
            'error_message' => $errorMessage,
            'result' => $result,
            'failed_at' => now(),
        ])->save();

        return $command->refresh();
    }
}
