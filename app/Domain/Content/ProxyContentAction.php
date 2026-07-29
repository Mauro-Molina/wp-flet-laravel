<?php

namespace App\Domain\Content;

use App\Domain\Licensing\LicenseValidator;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ProxyContentAction
{
    public function __construct(
        private readonly LicenseValidator $licenses,
        private readonly ContentProxyService $proxy,
    ) {}

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     */
    public function execute(
        Site $site,
        User $user,
        string $method,
        string $wpRelativePath,
        ?array $body = null,
        array $query = [],
    ): ContentProxyResult {
        if (! $site->isConnected()) {
            throw new AuthorizationException('Site is not connected.');
        }

        $this->licenses->assertContentAllowed($site);

        $result = $this->proxy->proxy($site, $method, $wpRelativePath, $body, $query);

        return $result;
    }
}
