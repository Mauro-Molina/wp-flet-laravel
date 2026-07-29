<?php

namespace App\Domain\Content;

use App\Domain\Hmac\HmacService;
use App\FakeAgent\InternalFakeAgentClient;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ContentProxyService
{
    public function __construct(
        private readonly HmacService $hmac,
        private readonly InternalFakeAgentClient $fakeAgent,
    ) {}

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     */
    public function proxy(
        Site $site,
        string $method,
        string $wpRelativePath,
        ?array $body = null,
        array $query = [],
    ): ContentProxyResult {
        if (config('fake_agent.enabled')) {
            return $this->fakeAgent->dispatch($site, $method, $wpRelativePath, $body, $query);
        }

        $credential = $site->activeCredential()->first();

        if ($credential === null || ! $credential->isValid()) {
            throw new ContentProxyException(
                'Site has no active credentials for content proxy.',
                422,
                [['code' => 'no_credentials', 'message' => 'Site has no active credentials for content proxy.']],
            );
        }

        $url = $this->resolveUrl($site, $wpRelativePath, $query);
        $bodyJson = $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : '';
        $timestamp = (string) time();
        $signature = $this->hmac->sign($credential->plainSecret(), $timestamp, $bodyJson);

        try {
            $response = Http::timeout((int) config('content.timeout_seconds', 30))
                ->withHeaders([
                    'X-Site-Id' => $site->id,
                    'X-Timestamp' => $timestamp,
                    'X-Signature' => $signature,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->withBody($bodyJson, 'application/json')
                ->send(strtoupper($method), $url);
        } catch (ConnectionException) {
            throw new ContentProxyException(
                'Unable to reach the WordPress site agent.',
                502,
                [['code' => 'agent_unreachable', 'message' => 'Unable to reach the WordPress site agent.']],
            );
        } catch (RequestException $e) {
            if ($e->response !== null && $e->response->status() === 408) {
                throw new ContentProxyException(
                    'WordPress site agent timed out.',
                    504,
                    [['code' => 'agent_timeout', 'message' => 'WordPress site agent timed out.']],
                );
            }

            throw new ContentProxyException(
                'WordPress site agent request failed.',
                502,
                [['code' => 'agent_error', 'message' => 'WordPress site agent request failed.']],
            );
        }

        $decoded = json_decode($response->body(), true);

        return new ContentProxyResult(
            $response->status(),
            $decoded ?? $response->body(),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function resolveUrl(Site $site, string $wpRelativePath, array $query): string
    {
        $path = trim($wpRelativePath, '/');

        if (config('fake_agent.enabled')) {
            $base = config('fake_agent.base_url');
            $prefix = config('fake_agent.route_prefix');
            $url = rtrim($base, '/').'/'.trim($prefix, '/').'/'.$path;
        } else {
            $base = rtrim($site->url, '/');
            $prefix = trim(config('content.agent_path_prefix', 'wp-json/wp/v2'), '/');
            $url = $base.'/'.$prefix.'/'.$path;
        }

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}
