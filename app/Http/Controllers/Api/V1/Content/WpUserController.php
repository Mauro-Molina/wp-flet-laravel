<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Domain\Content\ProxyContentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WpUserController extends Controller
{
    use HandlesContentProxy;

    public function index(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'users', false));
    }

    public function store(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'POST',
            'users',
            true,
            $request->all(),
        ));
    }

    public function show(Request $request, string $site, int $user, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'users/'.$user, false));
    }

    public function update(Request $request, string $site, int $user, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'PATCH',
            'users/'.$user,
            true,
            $request->all(),
        ));
    }

    public function destroy(Request $request, string $site, int $user, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'DELETE', 'users/'.$user, true));
    }
}
