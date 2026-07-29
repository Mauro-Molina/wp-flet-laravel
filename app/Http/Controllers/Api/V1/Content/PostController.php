<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Domain\Content\ProxyContentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use HandlesContentProxy;

    public function index(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'posts', false));
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
            'posts',
            true,
            $request->all(),
        ));
    }

    public function show(Request $request, string $site, int $post, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'posts/'.$post, false));
    }

    public function update(Request $request, string $site, int $post, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'PATCH',
            'posts/'.$post,
            true,
            $request->all(),
        ));
    }

    public function destroy(Request $request, string $site, int $post, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'DELETE', 'posts/'.$post, true));
    }

    public function publish(Request $request, string $site, int $post, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'POST',
            'posts/'.$post.'/publish',
            true,
        ));
    }

    public function schedule(Request $request, string $site, int $post, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy(
            $action,
            $siteModel,
            $request,
            'POST',
            'posts/'.$post.'/schedule',
            true,
            $request->only(['date']),
        ));
    }
}
