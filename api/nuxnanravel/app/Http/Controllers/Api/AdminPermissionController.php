<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminPermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Permission::withCount('roles');

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('display_name', 'like', "%{$request->search}%")
                    ->orWhere('group', 'like', "%{$request->search}%");
            });
        }

        // Group filter
        if ($request->has('group') && $request->group) {
            $query->where('group', $request->group);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'group');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder)->orderBy('name', 'asc');

        // Pagination or all
        if ($request->has('all') && $request->all === 'true') {
            $permissions = $query->get();

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        }

        $perPage = $request->get('per_page', 50);
        $permissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $permissions->items(),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }

    /**
     * Get permissions grouped by group name
     *
     * @return JsonResponse
     */
    public function groups()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $grouped = $permissions->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Store a newly created permission.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = Permission::create([
            'name' => strtolower(str_replace(' ', '-', $request->name)),
            'display_name' => $request->display_name,
            'description' => $request->description,
            'group' => strtolower($request->group),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้าง Permission สำเร็จ',
            'data' => $permission,
        ], 201);
    }

    /**
     * Bulk create permissions
     *
     * @return JsonResponse
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*.name' => 'required|string|max:255|unique:permissions,name',
            'permissions.*.display_name' => 'nullable|string|max:255',
            'permissions.*.description' => 'nullable|string|max:500',
            'permissions.*.group' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $created = [];
        foreach ($request->permissions as $permissionData) {
            $permission = Permission::create([
                'name' => strtolower(str_replace(' ', '-', $permissionData['name'])),
                'display_name' => $permissionData['display_name'] ?? null,
                'description' => $permissionData['description'] ?? null,
                'group' => strtolower($permissionData['group']),
            ]);
            $created[] = $permission;
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้าง '.count($created).' Permissions สำเร็จ',
            'data' => $created,
        ], 201);
    }

    /**
     * Display the specified permission.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $permission = Permission::with('roles')->find($id);

        if (! $permission) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Permission',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }

    /**
     * Update the specified permission.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (! $permission) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Permission',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('permissions')->ignore($id)],
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $permission->name = strtolower(str_replace(' ', '-', $request->name));
        }
        if ($request->has('display_name')) {
            $permission->display_name = $request->display_name;
        }
        if ($request->has('description')) {
            $permission->description = $request->description;
        }
        if ($request->has('group')) {
            $permission->group = strtolower($request->group);
        }

        $permission->save();

        return response()->json([
            'success' => true,
            'message' => 'อัปเดต Permission สำเร็จ',
            'data' => $permission,
        ]);
    }

    /**
     * Remove the specified permission.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $permission = Permission::withCount('roles')->find($id);

        if (! $permission) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Permission',
            ], 404);
        }

        // Check if permission is assigned to any roles
        if ($permission->roles_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมี {$permission->roles_count} Role ใช้ Permission นี้อยู่",
            ], 400);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ Permission สำเร็จ',
        ]);
    }
}
