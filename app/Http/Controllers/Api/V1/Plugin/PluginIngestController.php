<?php

namespace App\Http\Controllers\Api\V1\Plugin;

use App\Domain\Security\IngestLoginAttemptsAction;
use App\Domain\Security\IngestSecurityScanAction;
use App\Domain\Updates\SyncPendingUpdatesAction;
use App\Domain\Uptime\IngestUptimeCheckAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\IngestLoginAttemptsRequest;
use App\Http\Requests\Plugin\IngestSecurityScanRequest;
use App\Http\Requests\Plugin\IngestUptimeRequest;
use App\Http\Requests\Plugin\SyncUpdatesRequest;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class PluginIngestController extends Controller
{
    public function syncUpdates(SyncUpdatesRequest $request, SyncPendingUpdatesAction $action): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        $count = $action->execute($site, $request->input('updates'));

        return ApiResponse::success(['synced' => $count]);
    }

    public function securityScan(IngestSecurityScanRequest $request, IngestSecurityScanAction $action): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        $scan = $action->execute($site, $request->validated());

        return ApiResponse::success(['scan_id' => $scan->id], status: 201);
    }

    public function loginAttempts(IngestLoginAttemptsRequest $request, IngestLoginAttemptsAction $action): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        $count = $action->execute($site, $request->input('attempts'));

        return ApiResponse::success(['ingested' => $count]);
    }

    public function uptime(IngestUptimeRequest $request, IngestUptimeCheckAction $action): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        $check = $action->execute($site, $request->validated());

        return ApiResponse::success(['check_id' => $check->id], status: 201);
    }
}
