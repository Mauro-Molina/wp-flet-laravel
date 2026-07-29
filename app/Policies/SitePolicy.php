<?php

namespace App\Policies;

use App\Domain\Rbac\Permissions;
use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::SITES_VIEW);
    }

    public function view(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_VIEW) && $user->hasSiteAccess($site->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::SITES_MANAGE) || $user->can(Permissions::SITES_CONNECT);
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_MANAGE);
    }

    public function update(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_MANAGE);
    }

    public function connect(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_CONNECT) || $user->can(Permissions::SITES_MANAGE);
    }

    public function disconnect(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_MANAGE);
    }

    public function rotateCredentials(User $user, Site $site): bool
    {
        return $user->can(Permissions::SITES_MANAGE);
    }

    public function viewContent(User $user, Site $site): bool
    {
        return $user->can(Permissions::CONTENT_VIEW) && $user->hasSiteAccess($site->id);
    }

    public function manageContent(User $user, Site $site): bool
    {
        return $user->can(Permissions::CONTENT_MANAGE) && $user->hasSiteAccess($site->id);
    }
}
