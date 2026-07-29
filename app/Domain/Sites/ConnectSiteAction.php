<?php

namespace App\Domain\Sites;

use App\Domain\Licensing\LicenseValidator;
use App\Domain\Tenancy\TenantContext;
use App\Models\Site;

class ConnectSiteAction
{
    public function __construct(
        private readonly CredentialService $credentials,
        private readonly LicenseValidator $licenseValidator,
    ) {}

    /**
     * @return array{site: Site, credential: \App\Models\SiteCredential, secret: string, version: int}
     */
    public function execute(Site $site): array
    {
        TenantContext::set($site->tenant_id);

        $site->forceFill([
            'disconnected_at' => null,
            'status' => 'pending',
        ])->save();

        $this->licenseValidator->activate($site);

        $hasCredentials = $site->credentials()->exists();
        $issued = $hasCredentials
            ? $this->credentials->rotate($site)
            : $this->credentials->issue($site);

        return [
            'site' => $site->refresh(),
            'credential' => $issued['credential'],
            'secret' => $issued['plain_secret'],
            'version' => $issued['credential']->version,
        ];
    }
}
