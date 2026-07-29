<?php

namespace App\FakeAgent;

/**
 * In-memory stub store — NOT the real WordPress plugin.
 */
class FakeAgentStore
{
    /** @var array<string, array{posts: array<int, array<string, mixed>>, pages: array<int, array<string, mixed>>, users: array<int, array<string, mixed>>, settings: array<string, mixed>}> */
    private static array $sites = [];

    /**
     * @return array{posts: array<int, array<string, mixed>>, pages: array<int, array<string, mixed>>, users: array<int, array<string, mixed>>, settings: array<string, mixed>}
     */
    private static function bucket(string $siteId): array
    {
        if (! isset(self::$sites[$siteId])) {
            self::$sites[$siteId] = [
                'posts' => [
                    1 => self::makePost(1, 'Welcome', 'publish', null),
                ],
                'pages' => [
                    1 => self::makePage(1, 'Home', 'publish', 0),
                ],
                'users' => [
                    1 => self::makeUser(1, 'admin@example.com', 'administrator'),
                ],
                'settings' => [
                    'title' => 'WP Fleet Demo',
                    'tagline' => 'Just another WordPress site',
                    'timezone' => 'UTC',
                    'date_format' => 'F j, Y',
                ],
                'next_post_id' => 2,
                'next_page_id' => 2,
                'next_user_id' => 2,
            ];
        }

        return self::$sites[$siteId];
    }

    public static function reset(): void
    {
        self::$sites = [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPosts(string $siteId): array
    {
        return array_values(self::bucket($siteId)['posts']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function createPost(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_post_id']++;
        $post = self::makePost(
            $id,
            self::extractTitle($payload),
            $payload['status'] ?? 'draft',
            $payload['date'] ?? null,
            self::extractContent($payload),
        );
        self::$sites[$siteId]['posts'][$id] = $post;

        return $post;
    }

    public static function getPost(string $siteId, int $id): ?array
    {
        return self::bucket($siteId)['posts'][$id] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function updatePost(string $siteId, int $id, array $payload): ?array
    {
        $bucket = &self::$sites[$siteId];
        if (! isset($bucket['posts'][$id])) {
            return null;
        }

        $post = $bucket['posts'][$id];

        if (isset($payload['title'])) {
            $post['title']['rendered'] = self::extractTitle($payload);
        }
        if (isset($payload['content'])) {
            $post['content']['rendered'] = self::extractContent($payload);
        }
        if (isset($payload['status'])) {
            $post['status'] = $payload['status'];
        }
        if (isset($payload['date'])) {
            $post['date'] = $payload['date'];
        }

        $bucket['posts'][$id] = $post;

        return $post;
    }

    public static function deletePost(string $siteId, int $id): bool
    {
        if (! isset(self::$sites[$siteId]['posts'][$id])) {
            return false;
        }

        unset(self::$sites[$siteId]['posts'][$id]);

        return true;
    }

    public static function publishPost(string $siteId, int $id): ?array
    {
        return self::updatePost($siteId, $id, ['status' => 'publish', 'date' => now()->toIso8601String()]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function schedulePost(string $siteId, int $id, array $payload): ?array
    {
        return self::updatePost($siteId, $id, [
            'status' => 'future',
            'date' => $payload['date'] ?? now()->addDay()->toIso8601String(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPages(string $siteId): array
    {
        return array_values(self::bucket($siteId)['pages']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function createPage(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_page_id']++;
        $page = self::makePage(
            $id,
            self::extractTitle($payload),
            $payload['status'] ?? 'draft',
            (int) ($payload['parent'] ?? 0),
            self::extractContent($payload),
        );
        self::$sites[$siteId]['pages'][$id] = $page;

        return $page;
    }

    public static function getPage(string $siteId, int $id): ?array
    {
        return self::bucket($siteId)['pages'][$id] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function updatePage(string $siteId, int $id, array $payload): ?array
    {
        $bucket = &self::$sites[$siteId];
        if (! isset($bucket['pages'][$id])) {
            return null;
        }

        $page = $bucket['pages'][$id];

        if (isset($payload['title'])) {
            $page['title']['rendered'] = self::extractTitle($payload);
        }
        if (isset($payload['content'])) {
            $page['content']['rendered'] = self::extractContent($payload);
        }
        if (isset($payload['status'])) {
            $page['status'] = $payload['status'];
        }
        if (array_key_exists('parent', $payload)) {
            $page['parent'] = (int) $payload['parent'];
        }

        $bucket['pages'][$id] = $page;

        return $page;
    }

    public static function deletePage(string $siteId, int $id): bool
    {
        if (! isset(self::$sites[$siteId]['pages'][$id])) {
            return false;
        }

        unset(self::$sites[$siteId]['pages'][$id]);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listUsers(string $siteId): array
    {
        return array_values(self::bucket($siteId)['users']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function inviteUser(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_user_id']++;
        $user = self::makeUser(
            $id,
            (string) ($payload['email'] ?? 'user'.$id.'@example.com'),
            (string) ($payload['role'] ?? 'subscriber'),
            (string) ($payload['username'] ?? 'user'.$id),
        );
        self::$sites[$siteId]['users'][$id] = $user;

        return $user;
    }

    public static function getUser(string $siteId, int $id): ?array
    {
        return self::bucket($siteId)['users'][$id] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function updateUser(string $siteId, int $id, array $payload): ?array
    {
        $bucket = &self::$sites[$siteId];
        if (! isset($bucket['users'][$id])) {
            return null;
        }

        $user = $bucket['users'][$id];

        if (isset($payload['role'])) {
            $user['roles'] = [(string) $payload['role']];
        }
        if (isset($payload['email'])) {
            $user['email'] = (string) $payload['email'];
        }

        $bucket['users'][$id] = $user;

        return $user;
    }

    public static function deleteUser(string $siteId, int $id, ?int $reassignTo = null): bool
    {
        if (! isset(self::$sites[$siteId]['users'][$id])) {
            return false;
        }

        unset(self::$sites[$siteId]['users'][$id]);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSettings(string $siteId): array
    {
        return self::bucket($siteId)['settings'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function updateSettings(string $siteId, array $payload): array
    {
        $bucket = &self::$sites[$siteId];
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $bucket['settings'][$key] = $value;
            }
        }

        return $bucket['settings'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function makePost(int $id, string $title, string $status, ?string $date, string $content = ''): array
    {
        return [
            'id' => $id,
            'date' => $date ?? now()->toIso8601String(),
            'status' => $status,
            'title' => ['rendered' => $title],
            'content' => ['rendered' => $content !== '' ? $content : '<p>'.$title.'</p>'],
            'slug' => strtolower(str_replace(' ', '-', $title)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function makePage(int $id, string $title, string $status, int $parent, string $content = ''): array
    {
        $page = self::makePost($id, $title, $status, null, $content);
        $page['parent'] = $parent;

        return $page;
    }

    /**
     * @return array<string, mixed>
     */
    private static function makeUser(int $id, string $email, string $role, string $username = 'user'): array
    {
        return [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'roles' => [$role],
            'name' => ucfirst($username),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function extractTitle(array $payload): string
    {
        if (isset($payload['title']) && is_array($payload['title'])) {
            return (string) ($payload['title']['raw'] ?? $payload['title']['rendered'] ?? 'Untitled');
        }

        return (string) ($payload['title'] ?? 'Untitled');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function extractContent(array $payload): string
    {
        if (isset($payload['content']) && is_array($payload['content'])) {
            return (string) ($payload['content']['raw'] ?? $payload['content']['rendered'] ?? '');
        }

        return (string) ($payload['content'] ?? '');
    }
}
