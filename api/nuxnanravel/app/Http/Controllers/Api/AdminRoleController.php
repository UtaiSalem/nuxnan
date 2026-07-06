<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AdminRoleController extends Controller
{
    /**
     * Display a listing of roles.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Role::withCount(['users', 'permissions']);

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('display_name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination or all
        if ($request->has('all') && $request->all === 'true') {
            $roles = $query->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        }

        $perPage = $request->get('per_page', 15);
        $roles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    /**
     * Get roles data for DataTables (server-side processing)
     *
     * @return JsonResponse
     */
    public function datatable(Request $request)
    {
        $query = Role::withCount(['users', 'permissions'])->select('roles.*');

        // Apply status filter
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('status', $request->status === 'active');
        }

        return DataTables::eloquent($query)
            ->addColumn('badge_color', function ($role) {
                $colors = [
                    'SUPER_ADMIN' => 'red',
                    'ADMIN' => 'purple',
                    'MODERATOR' => 'blue',
                    'INSTRUCTOR' => 'green',
                    'STUDENT' => 'gray',
                ];

                return $colors[$role->name] ?? 'indigo';
            })
            ->addColumn('is_system', function ($role) {
                $systemRoles = ['SUPER_ADMIN', 'ADMIN', 'STUDENT'];

                return in_array($role->name, $systemRoles);
            })
            ->editColumn('status', function ($role) {
                return $role->status ? 'active' : 'inactive';
            })
            ->editColumn('created_at', function ($role) {
                return $role->created_at?->format('Y-m-d H:i');
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('display_name', 'like', "%{$keyword}%");
            })
            ->toJson();
    }

    /**
     * Store a newly created role.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => strtoupper($request->name),
            'display_name' => $request->display_name,
            'description' => $request->description,
            'status' => true,
        ]);

        // Assign permissions
        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->givePermissionTo($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้าง Role สำเร็จ',
            'data' => $role->load('permissions'),
        ], 201);
    }

    /**
     * Display the specified role.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $role = Role::with('permissions')->withCount(['users', 'permissions'])->find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update the specified role.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        // Prevent modifying system roles name
        $systemRoles = ['SUPER_ADMIN', 'ADMIN', 'STUDENT'];
        if (in_array($role->name, $systemRoles) && $request->has('name') && $request->name !== $role->name) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเปลี่ยนชื่อ Role ระบบได้',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($id)],
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update role
        $role->fill($request->only(['display_name', 'description', 'status']));

        if ($request->has('name') && ! in_array($role->name, $systemRoles)) {
            $role->name = strtoupper($request->name);
        }

        $role->save();

        // Update permissions (except for SUPER_ADMIN)
        if ($request->has('permissions') && $role->name !== 'SUPER_ADMIN') {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดต Role สำเร็จ',
            'data' => $role->load('permissions'),
        ]);
    }

    /**
     * Remove the specified role.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $role = Role::withCount('users')->find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        // Prevent deleting system roles
        $systemRoles = ['SUPER_ADMIN', 'ADMIN', 'STUDENT'];
        if (in_array($role->name, $systemRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบ Role ระบบได้',
            ], 403);
        }

        // Check if role has users
        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีผู้ใช้ {$role->users_count} คน ใช้ Role นี้อยู่",
            ], 400);
        }

        // Detach all permissions
        $role->permissions()->detach();

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ Role สำเร็จ',
        ]);
    }

    /**
     * Get all available permissions grouped
     *
     * @return JsonResponse
     */
    public function permissions()
    {
        $permissions = Permission::all()->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
