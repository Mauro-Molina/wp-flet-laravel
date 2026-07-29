<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Domain\Content\ProxyContentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use HandlesContentProxy;

    public function show(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'settings', false));
    }

    public function update(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'PATCH',
            'settings',
            true,
            $request->all(),
        ));
    }
}
