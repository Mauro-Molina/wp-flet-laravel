<?php

namespace App\Jobs;

use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Heavy diffing from heartbeat payload (plugins/themes changed).
 */
class ProcessHeartbeatSnapshotJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $siteId,
        public readonly ?array $plugins,
        public readonly ?array $themes,
    ) {}

    public function handle(): void
    {
        $site = Site::findForPlugin($this->siteId);

        if ($site === null) {
            return;
        }

        TenantContext::set($site->tenant_id);

        $site->forceFill([
            'plugins_snapshot' => $this->plugins,
            'themes_snapshot' => $this->themes,
        ])->save();
    }
}
