<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePlearndAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        // Check if user is authenticated and is a Plearnd Admin or Super Admin
        if (!$user || (!$user->isPlearndAdmin() && !$user->isSuperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Plearnd Admin or Super Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
