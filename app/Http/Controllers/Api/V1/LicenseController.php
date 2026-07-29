<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Licensing\LicenseValidator;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class LicenseController extends Controller
{
    public function validateSite(string $site, LicenseValidator $validator): JsonResponse
    {
        $siteModel = Site::query()->with('license')->findOrFail($site);
        $this->authorize('view', $siteModel);

        return ApiResponse::success($validator->validateForSite($siteModel));
    }
}
