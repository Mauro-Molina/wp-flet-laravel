<?php

use App\Domain\Content\ContentProxyException;
use App\Domain\Tenancy\Exceptions\TenantContextMissingException;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api_v1.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
        then: function (): void {
            Route::middleware('api')
                ->prefix('plugin/v1')
                ->group(base_path('routes/plugin_v1.php'));

            if (config('fake_agent.enabled')) {
                Route::middleware('api')
                    ->prefix(config('fake_agent.route_prefix'))
                    ->group(base_path('routes/fake_agent.php'));
            }

            Route::post('webhooks/stripe', [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])
                ->name('webhooks.stripe');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\AuthenticateJwt::class,
            'audit.sensitive' => \App\Http\Middleware\AuditSensitiveRequest::class,
            'plugin.hmac' => \App\Http\Middleware\VerifyPluginHmac::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Disable default api throttle; named limiters are applied per route group.
        $middleware->throttleApi('');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error('Too many requests.', 429, [
                    ['code' => 'rate_limit_exceeded', 'message' => 'Too many requests. Please try again later.'],
                ]);
            }
        });

        $exceptions->render(function (ContentProxyException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), $e->statusCode, $e->errors);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                $errors = collect($e->errors())->flatMap(
                    fn (array $messages, string $field) => collect($messages)->map(
                        fn (string $message) => ['code' => 'validation_error', 'field' => $field, 'message' => $message]
                    )
                )->values()->all();

                return ApiResponse::error('Validation failed.', 422, $errors);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error($e->getMessage() ?: 'Forbidden.', 403, [
                    ['code' => 'forbidden', 'message' => $e->getMessage() ?: 'Forbidden.'],
                ]);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error('Unauthenticated.', 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error('Resource not found.', 404, [
                    ['code' => 'not_found', 'message' => 'Resource not found.'],
                ]);
            }
        });

        $exceptions->render(function (TenantContextMissingException $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error($e->getMessage(), 500, [
                    ['code' => 'tenant_context_missing', 'message' => $e->getMessage()],
                ]);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*', 'plugin/*')) {
                return ApiResponse::error($e->getMessage() ?: 'HTTP error.', $e->getStatusCode());
            }
        });
    })->create();
