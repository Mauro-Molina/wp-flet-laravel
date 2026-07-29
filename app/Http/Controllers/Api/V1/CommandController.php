<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Commands\CreateCommandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commands\StoreCommandRequest;
use App\Models\Command;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function index(Request $request, string $site): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('view', $siteModel);

        $commands = Command::query()
            ->where('site_id', $siteModel->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return ApiResponse::success($commands->map(fn (Command $cmd) => $this->commandPayload($cmd))->values());
    }

    public function store(StoreCommandRequest $request, string $site, CreateCommandAction $action): JsonResponse
    {
        $siteModel = Site::query()->findOrFail($site);
        $this->authorize('create', Command::class);
        $this->authorize('view', $siteModel);

        $command = $action->execute(
            $siteModel,
            $request->string('type')->toString(),
            $request->string('idempotency_key')->toString(),
            $request->input('payload'),
            $request->user(),
        );

        return ApiResponse::success($this->commandPayload($command), status: 201);
    }

    public function show(string $command): JsonResponse
    {
        $model = Command::query()->findOrFail($command);
        $this->authorize('view', $model);

        return ApiResponse::success($this->commandPayload($model));
    }

    /**
     * @return array<string, mixed>
     */
    private function commandPayload(Command $command): array
    {
        return [
            'id' => $command->id,
            'site_id' => $command->site_id,
            'tenant_id' => $command->tenant_id,
            'type' => $command->type,
            'payload' => $command->payload,
            'status' => $command->status->value,
            'idempotency_key' => $command->idempotency_key,
            'result' => $command->result,
            'error_message' => $command->error_message,
            'expires_at' => $command->expires_at?->toIso8601String(),
            'completed_at' => $command->completed_at?->toIso8601String(),
            'failed_at' => $command->failed_at?->toIso8601String(),
            'created_at' => $command->created_at?->toIso8601String(),
        ];
    }
}
