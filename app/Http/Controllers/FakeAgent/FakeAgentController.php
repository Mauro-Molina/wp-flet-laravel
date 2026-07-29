<?php

namespace App\Http\Controllers\FakeAgent;

use App\FakeAgent\FakeAgentStore;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Development stub simulating wp/v2 REST responses — NOT the real plugin.
 */
class FakeAgentController extends Controller
{
    public function listPosts(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::listPosts($site->id));
    }

    public function createPost(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::createPost($site->id, $request->all()), 201);
    }

    public function showPost(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $post = FakeAgentStore::getPost($site->id, $id);

        if ($post === null) {
            return $this->notFound('post');
        }

        return response()->json($post);
    }

    public function updatePost(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $post = FakeAgentStore::updatePost($site->id, $id, $request->all());

        if ($post === null) {
            return $this->notFound('post');
        }

        return response()->json($post);
    }

    public function deletePost(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);

        if (! FakeAgentStore::deletePost($site->id, $id)) {
            return $this->notFound('post');
        }

        return response()->json(['deleted' => true, 'id' => $id]);
    }

    public function publishPost(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $post = FakeAgentStore::publishPost($site->id, $id);

        if ($post === null) {
            return $this->notFound('post');
        }

        return response()->json($post);
    }

    public function schedulePost(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $post = FakeAgentStore::schedulePost($site->id, $id, $request->all());

        if ($post === null) {
            return $this->notFound('post');
        }

        return response()->json($post);
    }

    public function listPages(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::listPages($site->id));
    }

    public function createPage(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::createPage($site->id, $request->all()), 201);
    }

    public function showPage(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $page = FakeAgentStore::getPage($site->id, $id);

        if ($page === null) {
            return $this->notFound('page');
        }

        return response()->json($page);
    }

    public function updatePage(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $page = FakeAgentStore::updatePage($site->id, $id, $request->all());

        if ($page === null) {
            return $this->notFound('page');
        }

        return response()->json($page);
    }

    public function deletePage(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);

        if (! FakeAgentStore::deletePage($site->id, $id)) {
            return $this->notFound('page');
        }

        return response()->json(['deleted' => true, 'id' => $id]);
    }

    public function listUsers(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::listUsers($site->id));
    }

    public function inviteUser(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::inviteUser($site->id, $request->all()), 201);
    }

    public function showUser(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $user = FakeAgentStore::getUser($site->id, $id);

        if ($user === null) {
            return $this->notFound('user');
        }

        return response()->json($user);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $user = FakeAgentStore::updateUser($site->id, $id, $request->all());

        if ($user === null) {
            return $this->notFound('user');
        }

        return response()->json($user);
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $site = $this->site($request);
        $reassign = $request->integer('reassign');

        if (! FakeAgentStore::deleteUser($site->id, $id, $reassign ?: null)) {
            return $this->notFound('user');
        }

        return response()->json(['deleted' => true, 'id' => $id]);
    }

    public function getSettings(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::getSettings($site->id));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $site = $this->site($request);

        return response()->json(FakeAgentStore::updateSettings($site->id, $request->all()));
    }

    private function site(Request $request): Site
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        return $site;
    }

    private function notFound(string $resource): JsonResponse
    {
        return ApiResponse::error(
            ucfirst($resource).' not found.',
            404,
            [['code' => 'rest_'.$resource.'_invalid_id', 'message' => ucfirst($resource).' not found.']],
        );
    }
}
