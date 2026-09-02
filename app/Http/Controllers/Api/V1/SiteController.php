<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sites\ConnectSiteAction;
use App\Domain\Sites\CreateSiteAction;
use App\Domain\Sites\DisconnectSiteAction;
use App\Domain\Sites\RotateCredentialsAction;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sites\StoreSiteRequest;
use App\Http\Requests\Sites\UpdateSiteRequest;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Site::query()->with('license')->orderBy('name');

        if (! $user->hasAnyRole(['Owner', 'Admin'])) {
            $query->whereHas('accessGrants', fn ($q) => $q->where('user_id', $user->id));
        }

        return ApiResponse::success($query->get()->map(fn (Site $site) => $this->sitePayload($site))->values());
    }

    public function store(StoreSiteRequest $request, CreateSiteAction $action): JsonResponse
    {
        $this->authorize('create', Site::class);

        $withCredentials = $request->boolean('with_credentials');
        $result = $action->execute(
            $request->string('name')->toString(),
            $request->string('url')->toString(),
            $withCredentials,
        );

        $data = $this->sitePayload($result['site']);

        if ($result['secret'] !== null) {
            $data['credentials'] = [
                'secret' => $result['secret'],
                'version' => $result['version'],
                'warning' => 'Store this secret securely. It will not be shown again.',
            ];
        }

        return ApiResponse::success($data, status: 201);
    }

    public function show(string $site): JsonResponse
    {
        $model = Site::query()->with('license')->findOrFail($site);
        $this->authorize('view', $model);

        return ApiResponse::success($this->sitePayload($model));
    }

    public function update(UpdateSiteRequest $request, string $site): JsonResponse
    {
        $model = Site::query()->findOrFail($site);
        $this->authorize('update', $model);

        $model->forceFill($request->validated())->save();

        return ApiResponse::success($this->sitePayload($model->refresh()));
    }

    public function destroy(string $site): JsonResponse
    {
        $model = Site::query()->findOrFail($site);
        $this->authorize('delete', $model);
        $model->delete();

        return ApiResponse::success(null);
    }

    public function connect(string $site, ConnectSiteAction $action): JsonResponse
    {
        $model = Site::query()->findOrFail($site);
        $this->authorize('connect', $model);

        $result = $action->execute($model);

        return ApiResponse::success([
            'site' => $this->sitePayload($result['site']),
            'credentials' => [
                'secret' => $result['secret'],
                'version' => $result['version'],
                'warning' => 'Store this secret securely. It will not be shown again.',
            ],
        ]);
    }

    public function disconnect(string $site, DisconnectSiteAction $action): JsonResponse
    {
        $model = Site::query()->findOrFail($site);
        $this->authorize('disconnect', $model);

        $disconnected = $action->execute($model);

        return ApiResponse::success($this->sitePayload($disconnected));
    }

    public function regenerateCredentials(string $site, RotateCredentialsAction $action): JsonResponse
    {
        $model = Site::query()->findOrFail($site);
        $this->authorize('rotateCredentials', $model);

        $result = $action->execute($model);

        return ApiResponse::success([
            'site_id' => $model->id,
            'credentials' => [
                'secret' => $result['secret'],
                'version' => $result['version'],
                'warning' => 'Store this secret securely. It will not be shown again.',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sitePayload(Site $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'url' => $site->url,
            'status' => $site->status,
            'tenant_id' => $site->tenant_id,
            'last_seen_at' => $site->last_seen_at?->toIso8601String(),
            'connected_at' => $site->connected_at?->toIso8601String(),
            'disconnected_at' => $site->disconnected_at?->toIso8601String(),
            'license_status' => $site->license?->status?->value,
            'wp_version' => $site->wp_version,
            'php_version' => $site->php_version,
            'plugins_snapshot' => $site->plugins_snapshot,
            'themes_snapshot' => $site->themes_snapshot,
        ];
    }
}
