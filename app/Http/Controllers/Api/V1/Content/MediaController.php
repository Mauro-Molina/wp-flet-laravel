<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Domain\Content\ProxyContentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Media proxy.
 *
 * Upload accepts either:
 * - JSON: { "file_base64": "...", "filename": "photo.jpg", "mime_type": "image/jpeg" }
 * - multipart/form-data with field "file" (+ optional filename/title)
 *
 * Core always forwards JSON+base64 to the WP agent so HMAC stays on a JSON body.
 */
class MediaController extends Controller
{
    use HandlesContentProxy;

    public function index(Request $request, string $site, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'media', false));
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
            'media',
            true,
            $this->normalizeMediaBody($request),
        ));
    }

    public function show(Request $request, string $site, int $media, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('viewContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'GET', 'media/'.$media, false));
    }

    public function destroy(Request $request, string $site, int $media, ProxyContentAction $action): JsonResponse
    {
        $siteModel = $this->resolveSite($site);
        $this->authorize('manageContent', $siteModel);

        return $this->handleContentProxy(fn () => $this->proxy($action, $siteModel, $request, 'DELETE', 'media/'.$media, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMediaBody(Request $request): array
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $bytes = file_get_contents($file->getRealPath());

            return array_filter([
                'filename' => $request->input('filename') ?: $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_base64' => base64_encode($bytes === false ? '' : $bytes),
                'title' => $request->input('title'),
            ], fn ($v) => $v !== null && $v !== '');
        }

        return $request->all();
    }
}
