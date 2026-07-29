<?php

namespace App\Domain\Sites;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;

class DisconnectSiteAction
{
    public function __construct(private readonly CredentialService $credentials) {}

    public function execute(Site $site): Site
    {
        TenantContext::set($site->tenant_id);

        $this->credentials->revokeAll($site);

        $site->forceFill([
            'status' => 'disconnected',
            'disconnected_at' => now(),
        ])->save();

        return $site->refresh();
    }
}
