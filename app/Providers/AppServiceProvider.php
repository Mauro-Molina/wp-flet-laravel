<?php

namespace App\Providers;

use App\Domain\Events\EventBusInterface;
use App\Domain\Events\LaravelQueueEventBus;
use App\Domain\RateLimiting\PlanRateLimitResolver;
use App\Domain\Tenancy\TenantContext;
use App\Models\Command;
use App\Models\Site;
use App\Models\User;
use App\Policies\CommandPolicy;
use App\Policies\SitePolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventBusInterface::class, LaravelQueueEventBus::class);
    }

    public function boot(): void
    {
        Gate::policy(Site::class, SitePolicy::class);
        Gate::policy(Command::class, CommandPolicy::class);

        $this->configureRateLimiting();
        $this->configureScramble();

        Gate::define('viewApiDocs', function (?User $user): bool {
            if (app()->environment(['local', 'testing'])) {
                return true;
            }

            return $user !== null && $user->can('billing.view');
        });

        $this->app->terminating(function (): void {
            TenantContext::clear();
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute((int) config('rate_limits.auth.login_per_minute', 10))
                ->by('login:'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute((int) config('rate_limits.auth.register_per_minute', 5))
                ->by('register:'.$request->ip());
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            return Limit::perMinute((int) config('rate_limits.auth.refresh_per_minute', 20))
                ->by('refresh:'.$request->ip());
        });

        RateLimiter::for('auth-2fa', function (Request $request) {
            return Limit::perMinute((int) config('rate_limits.auth.two_factor_per_minute', 10))
                ->by('2fa:'.$request->ip());
        });

        RateLimiter::for('api-tenant', function (Request $request) {
            $tenantId = $request->attributes->get('tenant_id')
                ?? TenantContext::id()
                ?? 'anonymous';

            $max = app(PlanRateLimitResolver::class)->resolveForRequest($request);

            return Limit::perMinute($max)->by('tenant:'.$tenantId);
        });

        RateLimiter::for('plugin', function (Request $request) {
            $siteId = $request->header('X-Site-Id', $request->ip());

            return Limit::perMinute((int) config('rate_limits.plugin_per_minute', 120))
                ->by('plugin:'.$siteId);
        });
    }

    private function configureScramble(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });
    }
}
