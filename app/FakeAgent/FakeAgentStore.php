<?php

namespace App\FakeAgent;

/**
 * In-memory stub store — NOT the real WordPress plugin.
 */
class FakeAgentStore
{
    /** @var array<string, array<string, mixed>> */
    private static array $sites = [];

    /**
     * @return array<string, mixed>
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
                'categories' => [
                    1 => self::makeTerm(1, 'Uncategorized', 'uncategorized', 0, 'category'),
                ],
                'tags' => [],
                'media' => [],
                'settings' => [
                    'title' => 'WP Fleet Demo',
                    'tagline' => 'Just another WordPress site',
                    'timezone' => 'UTC',
                    'date_format' => 'F j, Y',
                ],
                'next_post_id' => 2,
                'next_page_id' => 2,
                'next_user_id' => 2,
                'next_category_id' => 2,
                'next_tag_id' => 1,
                'next_media_id' => 1,
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
        $post = self::applyPostMeta($siteId, $post, $payload);
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
        if (isset($payload['excerpt'])) {
            $post['excerpt'] = is_array($payload['excerpt'])
                ? $payload['excerpt']
                : ['rendered' => (string) $payload['excerpt']];
        }
        if (isset($payload['status'])) {
            $post['status'] = $payload['status'];
        }
        if (isset($payload['date'])) {
            $post['date'] = $payload['date'];
        }

        $post = self::applyPostMeta($siteId, $post, $payload);
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
     * @return list<array<string, mixed>>
     */
    public static function listCategories(string $siteId): array
    {
        return array_values(self::bucket($siteId)['categories']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function createCategory(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_category_id']++;
        $name = (string) ($payload['name'] ?? 'Category '.$id);
        $slug = (string) ($payload['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $term = self::makeTerm($id, $name, $slug, (int) ($payload['parent'] ?? 0), 'category');
        self::$sites[$siteId]['categories'][$id] = $term;

        return $term;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listTags(string $siteId, ?string $search = null): array
    {
        $tags = array_values(self::bucket($siteId)['tags']);
        if ($search === null || $search === '') {
            return $tags;
        }

        $needle = strtolower($search);

        return array_values(array_filter(
            $tags,
            fn (array $tag) => str_contains(strtolower((string) $tag['name']), $needle)
                || str_contains(strtolower((string) $tag['slug']), $needle)
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function createTag(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_tag_id']++;
        $name = (string) ($payload['name'] ?? 'Tag '.$id);
        $slug = (string) ($payload['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $term = self::makeTerm($id, $name, $slug, 0, 'post_tag');
        self::$sites[$siteId]['tags'][$id] = $term;

        return $term;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listMedia(string $siteId, int $page = 1, int $perPage = 20): array
    {
        $all = array_values(self::bucket($siteId)['media']);
        $offset = max(0, ($page - 1) * $perPage);

        return array_slice($all, $offset, $perPage);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function createMedia(string $siteId, array $payload): array
    {
        self::bucket($siteId);
        $id = self::$sites[$siteId]['next_media_id']++;
        $filename = (string) ($payload['filename'] ?? $payload['title'] ?? 'upload-'.$id.'.jpg');
        $mime = (string) ($payload['mime_type'] ?? $payload['mime'] ?? 'image/jpeg');
        $url = (string) ($payload['source_url'] ?? 'https://example.com/media/'.$filename);

        $media = [
            'id' => $id,
            'title' => ['rendered' => pathinfo($filename, PATHINFO_FILENAME) ?: $filename],
            'source_url' => $url,
            'mime_type' => $mime,
            'media_type' => str_starts_with($mime, 'image/') ? 'image' : 'file',
            'media_details' => [
                'file' => $filename,
                'width' => (int) ($payload['width'] ?? 1200),
                'height' => (int) ($payload['height'] ?? 800),
            ],
        ];
        self::$sites[$siteId]['media'][$id] = $media;

        return $media;
    }

    public static function getMedia(string $siteId, int $id): ?array
    {
        return self::bucket($siteId)['media'][$id] ?? null;
    }

    public static function deleteMedia(string $siteId, int $id): bool
    {
        if (! isset(self::$sites[$siteId]['media'][$id])) {
            return false;
        }

        unset(self::$sites[$siteId]['media'][$id]);

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
     * @param  array<string, mixed>  $post
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function applyPostMeta(string $siteId, array $post, array $payload): array
    {
        $author = $payload['author'] ?? $payload['author_id'] ?? null;
        if ($author !== null) {
            $post['author'] = (int) $author;
        } elseif (! isset($post['author'])) {
            $post['author'] = 1;
        }

        if (array_key_exists('categories', $payload) || array_key_exists('category_ids', $payload)) {
            $post['categories'] = self::resolveTerms(
                $siteId,
                'categories',
                $payload['categories'] ?? $payload['category_ids'] ?? []
            );
        } elseif (! isset($post['categories'])) {
            $post['categories'] = [self::bucket($siteId)['categories'][1]];
        }

        if (array_key_exists('tags', $payload) || array_key_exists('tag_ids', $payload)) {
            $post['tags'] = self::resolveTerms(
                $siteId,
                'tags',
                $payload['tags'] ?? $payload['tag_ids'] ?? []
            );
        } elseif (! isset($post['tags'])) {
            $post['tags'] = [];
        }

        $featured = $payload['featured_media'] ?? $payload['featured_image_id'] ?? null;
        if ($featured !== null) {
            $mediaId = (int) $featured;
            $post['featured_media'] = $mediaId;
            $media = self::getMedia($siteId, $mediaId);
            $post['featured_image_url'] = $media['source_url'] ?? null;
        } elseif (! array_key_exists('featured_media', $post)) {
            $post['featured_media'] = 0;
            $post['featured_image_url'] = null;
        }

        if (isset($payload['excerpt']) && ! isset($post['excerpt'])) {
            $post['excerpt'] = is_array($payload['excerpt'])
                ? $payload['excerpt']
                : ['rendered' => (string) $payload['excerpt']];
        }

        return $post;
    }

    /**
     * @param  list<mixed>  $terms
     * @return list<array{id: int, name: string, slug: string}>
     */
    private static function resolveTerms(string $siteId, string $bucketKey, array $terms): array
    {
        self::bucket($siteId);
        $resolved = [];

        foreach ($terms as $term) {
            if (is_int($term) || (is_string($term) && ctype_digit($term))) {
                $id = (int) $term;
                $existing = self::$sites[$siteId][$bucketKey][$id] ?? null;
                if ($existing !== null) {
                    $resolved[] = [
                        'id' => (int) $existing['id'],
                        'name' => (string) $existing['name'],
                        'slug' => (string) $existing['slug'],
                    ];
                }

                continue;
            }

            if (is_string($term) && $term !== '') {
                $created = $bucketKey === 'tags'
                    ? self::createTag($siteId, ['name' => $term])
                    : self::createCategory($siteId, ['name' => $term]);
                $resolved[] = [
                    'id' => (int) $created['id'],
                    'name' => (string) $created['name'],
                    'slug' => (string) $created['slug'],
                ];
            }
        }

        return $resolved;
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
            'excerpt' => ['rendered' => ''],
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'author' => 1,
            'categories' => [
                ['id' => 1, 'name' => 'Uncategorized', 'slug' => 'uncategorized'],
            ],
            'tags' => [],
            'featured_media' => 0,
            'featured_image_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function makePage(int $id, string $title, string $status, int $parent, string $content = ''): array
    {
        $page = self::makePost($id, $title, $status, null, $content);
        $page['parent'] = $parent;
        unset($page['categories'], $page['tags'], $page['featured_media'], $page['featured_image_url']);

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
     * @return array{id: int, name: string, slug: string, parent: int, taxonomy: string}
     */
    private static function makeTerm(int $id, string $name, string $slug, int $parent, string $taxonomy): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'parent' => $parent,
            'taxonomy' => $taxonomy,
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
