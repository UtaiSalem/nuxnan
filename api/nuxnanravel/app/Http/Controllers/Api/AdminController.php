<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Display a listing of users with pagination.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Filter by role if specified
        if ($request->has('role') && $request->role !== 'all') {
            $role = Role::where('name', strtoupper($request->role))->first();
            if ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('role_id', $role->id);
                });
            }
        }

        // Search by name or email if search query is provided
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Paginate results
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage, ['*'], 'page', $page);

        // Format response
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->profile_photo_url,
                'role' => $user->roles->first()->name ?? 'user',
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'created_at' => $user->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => [
                'data' => $formattedUsers,
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'nullable|in:admin,instructor,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        // Assign role if specified
        if ($request->has('role')) {
            $role = Role::where('name', strtoupper($request->role))->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->profile_photo_url,
                'role' => $user->roles->first()->name ?? 'user',
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'phone' => $user->phone_number ?? $user->phone,
                'reference_code' => $user->reference_code,
                'personal_code' => $user->personal_code,
                'verified' => $user->verified,
                'wallet_balance' => $user->wallet ?? $user->wallet_balance ?? 0,
                'points' => $user->points ?? 0,
                'is_super_admin' => $user->is_super_admin ?? false,
                'is_plearnd_admin' => $user->is_plearnd_admin ?? false,
                'courses_count' => $user->courses()->count() ?? 0,
                'completed_courses' => 0, // TODO: implement
                'referrals_count' => 0, // TODO: implement
                'login_count' => $user->login_count ?? 0,
            ]
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'nullable|in:admin,instructor,user',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        // Update role if specified
        if ($request->has('role')) {
            $role = Role::where('name', strtoupper($request->role))->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }

        // Update status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $user->email_verified_at = now();
                    break;
                case 'inactive':
                    $user->email_verified_at = null;
                    break;
                case 'suspended':
                    $user->verified = false;
                    break;
            }
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deleting super admins
        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Cannot delete Super Admin user'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
