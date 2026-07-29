<?php

namespace App\Policies;

use App\Domain\Rbac\Permissions;
use App\Models\Command;
use App\Models\User;

class CommandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::COMMANDS_VIEW);
    }

    public function view(User $user, Command $command): bool
    {
        return $user->can(Permissions::COMMANDS_VIEW)
            && $user->hasSiteAccess($command->site_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::COMMANDS_CREATE);
    }
}
