<?php

namespace App\Domain\Commands;

use App\Enums\CommandStatus;
use App\Domain\Tenancy\TenantContext;
use App\Models\Command;

class CompleteCommandAction
{
    public function execute(Command $command, ?array $result = null): Command
    {
        TenantContext::set($command->tenant_id);

        if (! $command->isPending()) {
            return $command;
        }

        $command->forceFill([
            'status' => CommandStatus::Completed,
            'result' => $result,
            'completed_at' => now(),
        ])->save();

        return $command->refresh();
    }
}
