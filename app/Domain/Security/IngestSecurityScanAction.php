<?php

namespace App\Domain\Security;

use App\Domain\Tenancy\TenantContext;
use App\Models\SecurityScan;
use App\Models\Site;

class IngestSecurityScanAction
{
    public function execute(Site $site, array $data): SecurityScan
    {
        TenantContext::set($site->tenant_id);

        return SecurityScan::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'scan_type' => $data['scan_type'],
            'status' => $data['status'] ?? 'completed',
            'score' => $data['score'] ?? null,
            'findings' => $data['findings'] ?? null,
            'scanned_at' => $data['scanned_at'] ?? now(),
        ]);
    }
}
