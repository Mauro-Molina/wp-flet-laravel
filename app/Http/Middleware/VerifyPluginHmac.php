<?php

namespace App\Http\Middleware;

use App\Domain\Hmac\HmacService;
use App\Domain\Tenancy\TenantContext;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VerifyPluginHmac
{
    public function __construct(private readonly HmacService $hmac) {}

    public function handle(Request $request, Closure $next): Response
    {
        $siteId = $request->header('X-Site-Id');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (! $siteId || ! $timestamp || ! $signature) {
            return ApiResponse::error('Unauthorized.', 401, [
                ['code' => 'hmac_required', 'message' => 'X-Site-Id, X-Timestamp, and X-Signature headers are required.'],
            ]);
        }

        $site = Site::findForPlugin($siteId);

        if ($site === null || $site->status === 'disconnected') {
            return ApiResponse::error('Unauthorized.', 401, [
                ['code' => 'invalid_site', 'message' => 'Site not found or disconnected.'],
            ]);
        }

        $credential = TenantContext::bypass(fn () => $site->activeCredential()->first());

        if ($credential === null || ! $credential->isValid()) {
            return ApiResponse::error('Unauthorized.', 401, [
                ['code' => 'invalid_credentials', 'message' => 'No active credentials for this site.'],
            ]);
        }

        try {
            $this->hmac->assertTimestampFresh((string) $timestamp);
            $payload = $request->getContent();
            $secret = $credential->plainSecret();

            if (! $this->hmac->verify($secret, (string) $timestamp, $payload, (string) $signature)) {
                return ApiResponse::error('Unauthorized.', 401, [
                    ['code' => 'invalid_signature', 'message' => 'HMAC signature verification failed.'],
                ]);
            }
        } catch (RuntimeException $e) {
            return ApiResponse::error('Unauthorized.', 401, [
                ['code' => 'hmac_error', 'message' => $e->getMessage()],
            ]);
        }

        TenantContext::set($site->tenant_id);
        $request->attributes->set('plugin_site', $site);
        $request->attributes->set('plugin_credential', $credential);
        $request->attributes->set('tenant_id', $site->tenant_id);

        return $next($request);
    }
}
