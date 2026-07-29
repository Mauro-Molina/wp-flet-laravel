<?php

namespace App\Domain\Sites;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;

class RotateCredentialsAction
{
    public function __construct(private readonly CredentialService $credentials) {}

    /**
     * @return array{credential: \App\Models\SiteCredential, secret: string, version: int}
     */
    public function execute(Site $site): array
    {
        TenantContext::set($site->tenant_id);

        $issued = $this->credentials->rotate($site);

        return [
            'credential' => $issued['credential'],
            'secret' => $issued['plain_secret'],
            'version' => $issued['credential']->version,
        ];
    }
}
