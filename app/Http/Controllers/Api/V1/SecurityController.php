<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\SecurityLoginAttempt;
use App\Models\SecurityScan;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class SecurityController extends Controller
{
    public function scans(string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $scans = SecurityScan::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('scanned_at')
            ->limit(50)
            ->get();

        return ApiResponse::success($scans);
    }

    public function loginAttempts(string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $attempts = SecurityLoginAttempt::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('attempted_at')
            ->limit(100)
            ->get();

        return ApiResponse::success($attempts);
    }
}
