<?php

namespace App\Domain\Commands;

use App\Enums\CommandStatus;
use App\Models\Command;

class ExpireStaleCommandsAction
{
    public function execute(?string $siteId = null): int
    {
        $query = Command::query()
            ->where('status', CommandStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query->update([
            'status' => CommandStatus::Expired,
        ]);
    }
}
