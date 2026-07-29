<?php

namespace App\Domain\Sites;

use App\Domain\Billing\SubscriptionService;
use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use App\Models\Tenant;

class CreateSiteAction
{
    public function __construct(
        private readonly ConnectSiteAction $connect,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @return array{site: Site, secret: string|null, version: int|null}
     */
    public function execute(string $name, string $url, bool $withCredentials = false): array
    {
        $tenant = Tenant::query()->findOrFail(TenantContext::idOrFail());
        $this->subscriptions->assertCanAddSite($tenant);

        $site = Site::query()->create([
            'tenant_id' => TenantContext::idOrFail(),
            'name' => $name,
            'url' => $url,
            'status' => 'pending',
        ]);

        if (! $withCredentials) {
            return ['site' => $site, 'secret' => null, 'version' => null];
        }

        $connected = $this->connect->execute($site);

        return [
            'site' => $connected['site'],
            'secret' => $connected['secret'],
            'version' => $connected['version'],
        ];
    }
}
