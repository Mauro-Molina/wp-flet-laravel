<?php

namespace Tests\Feature\Security;

use App\Domain\Tenancy\TenantContext;
use App\Models\Backup;
use App\Models\Command;
use App\Models\Site;
use App\Models\SiteCredential;
use App\Models\SiteLicense;
use App\Models\SitePendingUpdate;
use App\Models\TenantScopedModel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityReviewTest extends TestCase
{
    public function test_tenant_scoped_models_extend_base_class(): void
    {
        $expected = [
            Site::class,
            SiteCredential::class,
            SiteLicense::class,
            Command::class,
            Backup::class,
            SitePendingUpdate::class,
        ];

        foreach ($expected as $class) {
            $this->assertTrue(
                is_subclass_of($class, TenantScopedModel::class),
                "{$class} must extend TenantScopedModel for fail-closed tenant scoping."
            );
        }
    }

    public function test_sensitive_api_routes_have_throttle_middleware(): void
    {
        $required = [
            'auth.login' => 'throttle:auth-login',
            'auth.register' => 'throttle:auth-register',
            'auth.refresh' => 'throttle:auth-refresh',
            'auth.2fa.verify' => 'throttle:auth-2fa',
            'sites.index' => 'throttle:api-tenant',
            'plugin.heartbeat' => 'throttle:plugin',
        ];

        foreach ($required as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] must exist.");

            $middlewares = $route->gatherMiddleware();
            $this->assertContains(
                $middleware,
                $middlewares,
                "Route [{$routeName}] must include [{$middleware}]. Got: ".implode(', ', $middlewares)
            );
        }
    }

    public function test_tenant_scope_is_fail_closed(): void
    {
        TenantContext::clear();

        $this->expectException(\App\Domain\Tenancy\Exceptions\TenantContextMissingException::class);
        Site::query()->get();
    }
}
