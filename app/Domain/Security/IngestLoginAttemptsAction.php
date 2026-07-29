<?php

namespace App\Domain\Security;

use App\Domain\Tenancy\TenantContext;
use App\Models\SecurityLoginAttempt;
use App\Models\Site;

class IngestLoginAttemptsAction
{
    /**
     * @param  list<array<string, mixed>>  $attempts
     */
    public function execute(Site $site, array $attempts): int
    {
        TenantContext::set($site->tenant_id);
        $count = 0;

        foreach ($attempts as $attempt) {
            SecurityLoginAttempt::query()->create([
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
                'username' => $attempt['username'] ?? null,
                'ip_address' => $attempt['ip_address'] ?? null,
                'success' => (bool) ($attempt['success'] ?? false),
                'attempted_at' => $attempt['attempted_at'] ?? now(),
                'created_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }
}
