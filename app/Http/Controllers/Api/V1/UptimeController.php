<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Site;
use App\Models\UptimeCheck;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UptimeController extends Controller
{
    public function checks(Request $request, string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $checks = UptimeCheck::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('checked_at')
            ->limit((int) $request->query('limit', 100))
            ->get();

        return ApiResponse::success($checks);
    }

    public function incidents(string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $incidents = Incident::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return ApiResponse::success($incidents);
    }
}
