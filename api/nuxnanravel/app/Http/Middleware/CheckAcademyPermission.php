<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Services\AcademyGroupPermissionAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes academy permissions from the academy role, then explicit group grants.
 *
 * Group-derived permissions are not data-scoped until D-S4 is complete. Before
 * enabling a permission for a group, be aware that its members can see or
 * modify data across the whole academy.
 */
class CheckAcademyPermission
{
    public function __construct(private AcademyGroupPermissionAccessService $groupPermissionAccess) {}

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

        if ($role && $role->hasAnyPermission($permissions)) {
            return $next($request);
        }

        // Group permission access is academy-wide until D-S4 adds data scoping.
        // Before enabling a group permission, note that its members can see or
        // modify data across the whole academy.
        if ($this->groupPermissionAccess->hasAnyPermission($user, $academy, $permissions)) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
}
