<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends Controller
{
    /**
     * Display a listing of super admins.
     */
    public function index()
    {
        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();
        
        if (!$superAdminRole) {
            return response()->json([
                'message' => 'Super Admin role not found',
                'data' => []
            ], 404);
        }

        $superAdmins = User::whereHas('roles', function ($query) use ($superAdminRole) {
            $query->where('role_id', $superAdminRole->id);
        })->with('roles')->get();

        return response()->json([
            'message' => 'Super Admins retrieved successfully',
            'data' => $superAdmins
        ]);
    }

    /**
     * Assign Super Admin role to a user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->user_id);

        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'User is already a Super Admin'
            ], 400);
        }

        $user->assignRole('SUPER_ADMIN');

        return response()->json([
            'message' => 'Super Admin role assigned successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    /**
     * Remove Super Admin role from a user.
     */
    public function destroy($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'message' => 'User is not a Super Admin'
            ], 400);
        }

        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();
        $user->roles()->detach($superAdminRole->id);

        return response()->json([
            'message' => 'Super Admin role removed successfully',
            'data' => $user->load('roles')
        ]);
    }

    /**
     * Check if the authenticated user is a Super Admin.
     */
    public function check()
    {
        $user = auth()->user();

        return response()->json([
            'is_super_admin' => $user->isSuperAdmin(),
            'user' => $user
        ]);
    }
}
