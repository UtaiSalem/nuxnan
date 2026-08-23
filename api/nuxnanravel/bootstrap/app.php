<?php

use App\Http\Middleware\AuditRequest;
use App\Http\Middleware\CheckAcademyPermission;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsurePlearndAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Load admin routes with /api/admin prefix
            Route::middleware(['api'])
                ->prefix('api/admin')
                ->group(base_path('routes/admin/admin.php'));

            // Load course completion routes
            Route::middleware(['api'])
                ->prefix('api')
                ->group(base_path('routes/course-completion.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super-admin' => EnsureSuperAdmin::class,
            'plearnd_admin' => EnsurePlearndAdmin::class,
            'admin' => EnsureAdminRole::class,
            'permission' => CheckPermission::class,
            'academy.permission' => CheckAcademyPermission::class,
            'audit.log' => AuditRequest::class,
        ]);

        // Ensure CORS is applied to API routes
        $middleware->api(prepend: [
            HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON response for unauthenticated API requests
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login first.',
                ], 401);
            }
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ระบบกำลังรับคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
                    'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? 0),
                ], 429, $e->getHeaders());
            }
        });
    })->create();
