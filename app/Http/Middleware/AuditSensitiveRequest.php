<?php

namespace App\Http\Middleware;

use App\Domain\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically audits sensitive HTTP actions without relying on controllers.
 */
class AuditSensitiveRequest
{
    /**
     * @var list<string>
     */
    private array $sensitiveMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            in_array($request->method(), $this->sensitiveMethods, true)
            && $response->getStatusCode() < 400
            && auth()->check()
        ) {
            $this->audit->log(
                action: 'http.'.$request->method().'.'.($request->route()?->getName() ?? $request->path()),
                newValues: [
                    'path' => $request->path(),
                    'route' => $request->route()?->getName(),
                    'status' => $response->getStatusCode(),
                ],
            );
        }

        return $response;
    }
}
