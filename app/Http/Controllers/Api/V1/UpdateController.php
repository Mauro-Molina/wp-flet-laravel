<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Updates\CreateUpdateCommandAction;
use App\Domain\Updates\ListPendingUpdatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Updates\RunUpdateRequest;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function index(Request $request, string $site, ListPendingUpdatesAction $action): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $updates = $action->execute($siteModel, $request->query('type'));

        return ApiResponse::success($updates->map(fn ($u) => [
            'id' => $u->id,
            'update_type' => $u->update_type,
            'item_slug' => $u->item_slug,
            'item_name' => $u->item_name,
            'current_version' => $u->current_version,
            'available_version' => $u->available_version,
            'detected_at' => $u->detected_at?->toIso8601String(),
        ])->values());
    }

    public function store(RunUpdateRequest $request, string $site, CreateUpdateCommandAction $action): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $command = $action->execute(
            $siteModel,
            $request->string('update_type')->toString(),
            $request->string('idempotency_key')->toString(),
            $request->input('items'),
            $request->user(),
        );

        return ApiResponse::success([
            'command_id' => $command->id,
            'type' => $command->type,
            'status' => $command->status->value,
        ], status: 201);
    }

    public function show(string $site, string $command): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $model = $siteModel->commands()->findOrFail($command);

        return ApiResponse::success([
            'id' => $model->id,
            'type' => $model->type,
            'status' => $model->status->value,
            'result' => $model->result,
            'error_message' => $model->error_message,
            'completed_at' => $model->completed_at?->toIso8601String(),
        ]);
    }
}
