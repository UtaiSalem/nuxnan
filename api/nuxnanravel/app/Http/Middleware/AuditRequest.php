<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to automatically audit sensitive endpoints.
 *
 * Usage in routes:
 * Route::get('/sensitive', [Controller::class, 'method'])->middleware('audit.log:module,action');
 */
class AuditRequest
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $module = null, ?string $action = null): Response
    {
        $response = $next($request);

        // Only log if audit is enabled
        if (config('audit.enabled', true)) {
            $this->logRequest($request, $response, $module, $action);
        }

        return $response;
    }

    /**
     * Log the request after response is sent.
     */
    protected function logRequest(Request $request, Response $response, ?string $module, ?string $action): void
    {
        try {
            // Determine action from HTTP method if not provided
            $action = $action ?? $this->getActionFromMethod($request->method());

            // Determine module from route if not provided
            $module = $module ?? $this->getModuleFromRoute($request);

            $this->auditLogService->log(
                $action,
                null,
                null,
                null,
                $module,
                [
                    'route' => $request->route()?->getName(),
                    'params' => $request->route()?->parameters(),
                    'status_code' => $response->getStatusCode(),
                ]
            );
        } catch (\Exception $e) {
            // Don't let audit failures affect the request
            report($e);
        }
    }

    /**
     * Get action type from HTTP method.
     */
    protected function getActionFromMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'viewed',
        };
    }

    /**
     * Get module from route name.
     */
    protected function getModuleFromRoute(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        // Extract module from route name (e.g., 'admin.users.index' -> 'users')
        $parts = explode('.', $routeName);

        if (count($parts) >= 2) {
            return $parts[1] ?? null;
        }

        return null;
    }
}
