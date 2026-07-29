<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Backups\CreateBackupAction;
use App\Domain\Backups\RestoreBackupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backups\RestoreBackupRequest;
use App\Http\Requests\Backups\StoreBackupRequest;
use App\Models\Backup;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(Request $request, string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $backups = Backup::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($backups->map(fn ($b) => $this->payload($b))->values());
    }

    public function store(StoreBackupRequest $request, string $site, CreateBackupAction $action): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $backup = $action->execute(
            $siteModel,
            $request->string('idempotency_key')->toString(),
            $request->input('label'),
            $request->user(),
        );

        return ApiResponse::success($this->payload($backup), status: 201);
    }

    public function show(string $site, string $backup): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $model = Backup::query()->where('site_id', $siteModel->id)->findOrFail($backup);

        return ApiResponse::success($this->payload($model));
    }

    public function destroy(string $site, string $backup): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $model = Backup::query()->where('site_id', $siteModel->id)->findOrFail($backup);
        $model->delete();

        return ApiResponse::success(null);
    }

    public function restore(
        RestoreBackupRequest $request,
        string $site,
        string $backup,
        RestoreBackupAction $action,
    ): JsonResponse {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $backupModel = Backup::query()->where('site_id', $siteModel->id)->findOrFail($backup);

        $command = $action->execute(
            $siteModel,
            $backupModel,
            $request->string('idempotency_key')->toString(),
            $request->string('site_name_confirmation')->toString(),
            $request->boolean('confirmed_destructive'),
            $request->user(),
        );

        return ApiResponse::success([
            'command_id' => $command->id,
            'status' => $command->status->value,
        ], status: 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Backup $backup): array
    {
        return [
            'id' => $backup->id,
            'site_id' => $backup->site_id,
            'type' => $backup->type,
            'status' => $backup->status,
            'label' => $backup->label,
            'size_bytes' => $backup->size_bytes,
            'command_id' => $backup->command_id,
            'completed_at' => $backup->completed_at?->toIso8601String(),
            'created_at' => $backup->created_at?->toIso8601String(),
        ];
    }
}
