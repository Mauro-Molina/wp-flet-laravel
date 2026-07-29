<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\UpdateNotificationPreferenceRequest;
use App\Models\NotificationHistory;
use App\Models\NotificationPreference;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function preferences(Request $request): JsonResponse
    {
        $prefs = NotificationPreference::query()
            ->where('user_id', $request->user()->id)
            ->get();

        return ApiResponse::success($prefs);
    }

    public function updatePreference(
        UpdateNotificationPreferenceRequest $request,
    ): JsonResponse {
        $pref = NotificationPreference::query()->updateOrCreate(
            [
                'tenant_id' => TenantContext::idOrFail(),
                'user_id' => $request->user()->id,
                'event_type' => $request->string('event_type')->toString(),
                'channel' => $request->string('channel')->toString(),
            ],
            ['enabled' => $request->boolean('enabled')],
        );

        return ApiResponse::success($pref);
    }

    public function history(Request $request): JsonResponse
    {
        $history = NotificationHistory::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return ApiResponse::success($history);
    }
}
