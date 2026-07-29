<?php

namespace App\Http\Controllers\Api\V1\Plugin;

use App\Domain\Backups\CompleteBackupAction;
use App\Domain\Commands\CompleteCommandAction;
use App\Domain\Commands\FailCommandAction;
use App\Enums\CommandType;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\Command;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginCommandController extends Controller
{
    public function complete(
        Request $request,
        string $command,
        CompleteCommandAction $action,
        CompleteBackupAction $completeBackup,
    ): JsonResponse {
        $model = Command::query()->findOrFail($command);

        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');
        if ($model->site_id !== $site->id) {
            abort(404);
        }

        $completed = $action->execute($model, $request->input('result'));

        if ($completed->type === CommandType::BackupCreate->value) {
            $backup = Backup::query()->where('command_id', $completed->id)->first();
            if ($backup !== null) {
                $result = $request->input('result', []);
                $completeBackup->execute(
                    $backup,
                    isset($result['size_bytes']) ? (int) $result['size_bytes'] : null,
                    $result['storage_path'] ?? null,
                );
            }
        }

        return ApiResponse::success([
            'id' => $completed->id,
            'status' => $completed->status->value,
            'completed_at' => $completed->completed_at?->toIso8601String(),
        ]);
    }

    public function fail(
        Request $request,
        string $command,
        FailCommandAction $action,
    ): JsonResponse {
        $model = Command::query()->findOrFail($command);

        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');
        if ($model->site_id !== $site->id) {
            abort(404);
        }

        $failed = $action->execute(
            $model,
            $request->input('error_message'),
            $request->input('result'),
        );

        return ApiResponse::success([
            'id' => $failed->id,
            'status' => $failed->status->value,
            'failed_at' => $failed->failed_at?->toIso8601String(),
        ]);
    }
}
