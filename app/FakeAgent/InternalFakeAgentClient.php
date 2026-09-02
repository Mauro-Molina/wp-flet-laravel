<?php

namespace App\FakeAgent;

use App\Domain\Content\ContentProxyException;
use App\Domain\Content\ContentProxyResult;
use App\Models\Site;

/**
 * In-process dispatch to FakeAgentStore — avoids HTTP loopback in tests/dev.
 */
class InternalFakeAgentClient
{
    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     */
    public function dispatch(
        Site $site,
        string $method,
        string $wpRelativePath,
        ?array $body = null,
        array $query = [],
    ): ContentProxyResult {
        $segments = explode('/', trim($wpRelativePath, '/'));
        $resource = $segments[0] ?? '';
        $id = isset($segments[1]) && ctype_digit($segments[1]) ? (int) $segments[1] : null;
        $action = $segments[2] ?? null;
        $method = strtoupper($method);
        $payload = $body ?? [];

        return match ($resource) {
            'posts' => $this->handlePosts($site->id, $method, $id, $action, $payload),
            'pages' => $this->handlePages($site->id, $method, $id, $payload),
            'users' => $this->handleUsers($site->id, $method, $id, $payload, $query),
            'categories' => $this->handleCategories($site->id, $method, $id, $payload),
            'tags' => $this->handleTags($site->id, $method, $id, $payload, $query),
            'media' => $this->handleMedia($site->id, $method, $id, $payload, $query),
            'settings' => $this->handleSettings($site->id, $method, $payload),
            default => throw new ContentProxyException('Unknown WP resource.', 404, [
                ['code' => 'rest_no_route', 'message' => 'Unknown WP resource.'],
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePosts(string $siteId, string $method, ?int $id, ?string $action, array $payload): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            return new ContentProxyResult(200, FakeAgentStore::listPosts($siteId));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::createPost($siteId, $payload));
        }

        if ($method === 'GET' && $id !== null) {
            $post = FakeAgentStore::getPost($siteId, $id);

            return $post
                ? new ContentProxyResult(200, $post)
                : $this->notFound('post');
        }

        if (in_array($method, ['PATCH', 'PUT'], true) && $id !== null && $action === null) {
            $post = FakeAgentStore::updatePost($siteId, $id, $payload);

            return $post
                ? new ContentProxyResult(200, $post)
                : $this->notFound('post');
        }

        if ($method === 'DELETE' && $id !== null) {
            return FakeAgentStore::deletePost($siteId, $id)
                ? new ContentProxyResult(200, ['deleted' => true, 'id' => $id])
                : $this->notFound('post');
        }

        if ($method === 'POST' && $id !== null && $action === 'publish') {
            $post = FakeAgentStore::publishPost($siteId, $id);

            return $post
                ? new ContentProxyResult(200, $post)
                : $this->notFound('post');
        }

        if ($method === 'POST' && $id !== null && $action === 'schedule') {
            $post = FakeAgentStore::schedulePost($siteId, $id, $payload);

            return $post
                ? new ContentProxyResult(200, $post)
                : $this->notFound('post');
        }

        return $this->notFound('post');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePages(string $siteId, string $method, ?int $id, array $payload): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            return new ContentProxyResult(200, FakeAgentStore::listPages($siteId));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::createPage($siteId, $payload));
        }

        if ($method === 'GET' && $id !== null) {
            $page = FakeAgentStore::getPage($siteId, $id);

            return $page
                ? new ContentProxyResult(200, $page)
                : $this->notFound('page');
        }

        if (in_array($method, ['PATCH', 'PUT'], true) && $id !== null) {
            $page = FakeAgentStore::updatePage($siteId, $id, $payload);

            return $page
                ? new ContentProxyResult(200, $page)
                : $this->notFound('page');
        }

        if ($method === 'DELETE' && $id !== null) {
            return FakeAgentStore::deletePage($siteId, $id)
                ? new ContentProxyResult(200, ['deleted' => true, 'id' => $id])
                : $this->notFound('page');
        }

        return $this->notFound('page');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    private function handleUsers(string $siteId, string $method, ?int $id, array $payload, array $query): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            return new ContentProxyResult(200, FakeAgentStore::listUsers($siteId));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::inviteUser($siteId, $payload));
        }

        if ($method === 'GET' && $id !== null) {
            $user = FakeAgentStore::getUser($siteId, $id);

            return $user
                ? new ContentProxyResult(200, $user)
                : $this->notFound('user');
        }

        if (in_array($method, ['PATCH', 'PUT'], true) && $id !== null) {
            $user = FakeAgentStore::updateUser($siteId, $id, $payload);

            return $user
                ? new ContentProxyResult(200, $user)
                : $this->notFound('user');
        }

        if ($method === 'DELETE' && $id !== null) {
            $reassign = isset($query['reassign']) ? (int) $query['reassign'] : null;

            return FakeAgentStore::deleteUser($siteId, $id, $reassign)
                ? new ContentProxyResult(200, ['deleted' => true, 'id' => $id])
                : $this->notFound('user');
        }

        return $this->notFound('user');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCategories(string $siteId, string $method, ?int $id, array $payload): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            return new ContentProxyResult(200, FakeAgentStore::listCategories($siteId));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::createCategory($siteId, $payload));
        }

        return $this->notFound('category');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    private function handleTags(string $siteId, string $method, ?int $id, array $payload, array $query): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            $search = isset($query['search']) ? (string) $query['search'] : null;

            return new ContentProxyResult(200, FakeAgentStore::listTags($siteId, $search));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::createTag($siteId, $payload));
        }

        return $this->notFound('tag');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    private function handleMedia(string $siteId, string $method, ?int $id, array $payload, array $query): ContentProxyResult
    {
        if ($method === 'GET' && $id === null) {
            $page = max(1, (int) ($query['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($query['per_page'] ?? 20)));

            return new ContentProxyResult(200, FakeAgentStore::listMedia($siteId, $page, $perPage));
        }

        if ($method === 'POST' && $id === null) {
            return new ContentProxyResult(201, FakeAgentStore::createMedia($siteId, $payload));
        }

        if ($method === 'GET' && $id !== null) {
            $media = FakeAgentStore::getMedia($siteId, $id);

            return $media
                ? new ContentProxyResult(200, $media)
                : $this->notFound('media');
        }

        if ($method === 'DELETE' && $id !== null) {
            return FakeAgentStore::deleteMedia($siteId, $id)
                ? new ContentProxyResult(200, ['deleted' => true, 'id' => $id])
                : $this->notFound('media');
        }

        return $this->notFound('media');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSettings(string $siteId, string $method, array $payload): ContentProxyResult
    {
        if ($method === 'GET') {
            return new ContentProxyResult(200, FakeAgentStore::getSettings($siteId));
        }

        if (in_array($method, ['PATCH', 'PUT'], true)) {
            return new ContentProxyResult(200, FakeAgentStore::updateSettings($siteId, $payload));
        }

        return new ContentProxyResult(405, ['code' => 'rest_method_not_allowed', 'message' => 'Method not allowed.']);
    }

    private function notFound(string $resource): ContentProxyResult
    {
        return new ContentProxyResult(404, [
            'code' => 'rest_'.$resource.'_invalid_id',
            'message' => ucfirst($resource).' not found.',
        ]);
    }
}
