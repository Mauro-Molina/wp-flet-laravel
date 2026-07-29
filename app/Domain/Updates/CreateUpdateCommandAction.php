<?php

namespace App\Domain\Updates;

use App\Domain\Commands\CreateCommandAction;
use App\Enums\CommandType;
use App\Models\Command;
use App\Models\Site;
use App\Models\User;

class CreateUpdateCommandAction
{
    public function __construct(private readonly CreateCommandAction $createCommand) {}

    /**
     * @param  list<string>|null  $itemSlugs
     */
    public function execute(
        Site $site,
        string $updateType,
        string $idempotencyKey,
        ?array $itemSlugs = null,
        ?User $creator = null,
    ): Command {
        $commandType = match ($updateType) {
            'core' => CommandType::UpdateCore->value,
            'plugin' => count($itemSlugs ?? []) > 1
                ? CommandType::UpdateBatch->value
                : CommandType::UpdatePlugins->value,
            'theme' => CommandType::UpdateThemes->value,
            default => CommandType::UpdateBatch->value,
        };

        return $this->createCommand->execute(
            $site,
            $commandType,
            $idempotencyKey,
            [
                'update_type' => $updateType,
                'items' => $itemSlugs,
            ],
            $creator,
        );
    }
}
