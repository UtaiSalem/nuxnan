<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use App\Models\AcademyMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAcademyPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $academy = $request->route('academy');

        if (! $academy instanceof Academy) {
            $academy = Academy::find($academy);
        }

        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'Academy not found'], 404);
        }

        if ($academy->isAdmin($user)) {
            return $next($request);
        }

        $member = AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 2)
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Not a member of this academy'], 403);
        }

        if (empty($permissions)) {
            return $next($request);
        }

        $role = $member->academyRole;

        if (! $role) {
            return response()->json(['success' => false, 'message' => 'No role assigned'], 403);
        }

        if ($role->hasAnyPermission($permissions)) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
}
