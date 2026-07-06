<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Super Admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // If specific roles are required
        if (! empty($roles)) {
            if (! $user->hasAnyRole($roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้',
                ], 403);
            }
        } else {
            // Default admin roles check
            $adminRoles = ['SUPER_ADMIN', 'ADMIN', 'MODERATOR', 'INSTRUCTOR'];
            if (! $user->hasAnyRole($adminRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์เข้าถึงระบบ Admin',
                ], 403);
            }
        }

        return $next($request);
    }
}
