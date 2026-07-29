<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Domain\Content\ContentProxyException;
use App\Domain\Content\ContentProxyResult;
use App\Domain\Content\ProxyContentAction;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesContentProxy
{
    protected function handleContentProxy(callable $callback): JsonResponse
    {
        try {
            /** @var ContentProxyResult $result */
            $result = $callback();

            return $this->contentProxyResponse($result);
        } catch (ContentProxyException $e) {
            return ApiResponse::error($e->getMessage(), $e->statusCode, $e->errors);
        }
    }

    protected function contentProxyResponse(ContentProxyResult $result): JsonResponse
    {
        if ($result->isSuccessful()) {
            $status = $result->statusCode === 201 ? 201 : 200;

            return ApiResponse::success($result->body, status: $status);
        }

        $body = is_array($result->body) ? $result->body : [];
        $message = (string) ($body['message'] ?? 'Content proxy request failed.');
        $code = (string) ($body['code'] ?? 'wp_error');

        return ApiResponse::error($message, $result->statusCode, [
            ['code' => $code, 'message' => $message],
        ]);
    }

    protected function resolveSite(string $siteId): Site
    {
        return Site::query()->findOrFail($siteId);
    }

    protected function proxy(
        ProxyContentAction $action,
        Site $site,
        Request $request,
        string $method,
        string $path,
        bool $requiresManage,
        ?array $body = null,
    ): ContentProxyResult {
        return $action->execute(
            $site,
            $request->user(),
            $method,
            $path,
            $body,
            $request->query(),
        );
    }
}
