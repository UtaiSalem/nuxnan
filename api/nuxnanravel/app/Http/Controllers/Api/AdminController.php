<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\PlearndAdmin;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserDeletionImpactService;
use App\Services\Admin\UserDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function __construct(
        private UserDeletionImpactService $impactService,
        private UserDeletionService $deletionService
    ) {}

    /**
     * Display a listing of users with filtering and pagination.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'profile']);

        // Scope filter (SoftDeletes support)
        $scope = $request->get('scope', 'active');
        if ($scope === 'deleted') {
            $query->onlyTrashed();
        } elseif ($scope === 'all') {
            $query->withTrashed();
        }

        // Search filter (enhanced: name, email, phone, personal_code)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('personal_code', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', strtoupper($request->role));
            });
        }

        // Status filter (verified/unverified)
        if ($request->has('status') && $request->status) {
            if ($request->status === 'verified' || $request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified' || $request->status === 'inactive' || $request->status === 'pending') {
                $query->whereNull('email_verified_at');
            }
        }

        // Date range filter
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sorting (customizable)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'total_pending' => User::whereNull('email_verified_at')->count(),
            ],
        ]);
    }

    /**
     * Get users data for DataTables (server-side processing)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function datatable(Request $request)
    {
        $query = User::with('roles')->select('users.*');

        // Apply role filter
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', strtoupper($request->role));
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            if ($request->status === 'verified' || $request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified' || $request->status === 'inactive' || $request->status === 'pending') {
                $query->whereNull('email_verified_at');
            }
        }

        return DataTables::eloquent($query)
            ->addColumn('avatar', function ($user) {
                return $user->avatar ?? $user->profile_photo_url ?? '/storage/images/default-avatar.png';
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->map(function ($role) {
                    $colors = [
                        'SUPER_ADMIN' => 'red',
                        'ADMIN' => 'purple',
                        'MODERATOR' => 'blue',
                        'INSTRUCTOR' => 'green',
                        'USER' => 'gray',
                    ];

                    return [
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? $role->name,
                        'color' => $colors[$role->name] ?? 'gray',
                    ];
                });
            })
            ->addColumn('status', function ($user) {
                return $user->email_verified_at ? 'verified' : 'unverified';
            })
            ->addColumn('is_super_admin', function ($user) {
                return $user->isSuperAdmin();
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('Y-m-d H:i');
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->toJson();
    }

    /**
     * Get user statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // Role distribution
        $roleDistribution = Role::withCount('users')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'count' => $role->users_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'verified_users' => $verifiedUsers,
                'unverified_users' => $unverifiedUsers,
                'new_users_today' => $newUsersToday,
                'new_users_this_week' => $newUsersThisWeek,
                'new_users_this_month' => $newUsersThisMonth,
                'role_distribution' => $roleDistribution,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'nullable|string|exists:roles,name',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'email_verified_at' => now(),
        ]);

        // Assign roles
        if ($request->has('roles') && is_array($request->roles)) {
            $roleIds = Role::whereIn('name', array_map('strtoupper', $request->roles))->pluck('id');
            $user->roles()->sync($roleIds);
        } elseif ($request->has('role')) {
            $role = Role::where('name', strtoupper($request->role))->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }
        } else {
            // Default to USER role
            $userRole = Role::where('name', 'USER')->first();
            if ($userRole) {
                $user->roles()->attach($userRole->id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างผู้ใช้สำเร็จ',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::with(['roles', 'profile'])->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => strtolower($user->roles->first()?->name ?? 'user'),
                'avatar' => $user->avatar ?? $user->profile_photo_url,
                'roles' => $user->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'display_name' => $r->display_name ?? $r->name,
                ]),
                'permissions' => $user->getAllPermissions(),
                'status' => $user->email_verified_at ? 'verified' : 'unverified',
                'is_banned' => $user->is_banned ?? false,
                'is_super_admin' => $user->isSuperAdmin(),
                'is_plearnd_admin' => $user->is_plearnd_admin ?? false,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'phone_number' => $user->phone_number,
                'personal_code' => $user->personal_code,
                'reference_code' => $user->reference_code,
                'wallet' => $user->wallet ?? 0,
                'points' => $user->points ?? 0,
                'courses_count' => $user->courses()->count() ?? 0,
                'login_count' => $user->login_count ?? 0,
            ],
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], 404);
        }

        if ($request->has('role') && $request->role) {
            $request->merge(['role' => strtoupper($request->role)]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'password' => 'sometimes|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'nullable|string|exists:roles,name',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update basic fields
        $user->fill($request->only(['name', 'email', 'phone_number']));

        if ($request->has('status')) {
            match ($request->status) {
                'active' => $user->email_verified_at = $user->email_verified_at ?? now(),
                'inactive',
                'suspended' => $user->email_verified_at = null,
                default => null,
            };
        }

        if ($request->has('password') && $request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // จัดการ is_super_admin ผ่าน SUPER_ADMIN role
        if ($request->has('is_super_admin')) {
            $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();
            if ($superAdminRole) {
                if ($request->boolean('is_super_admin')) {
                    $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
                } else {
                    $user->roles()->detach($superAdminRole->id);
                }
            }
        }

        // จัดการ is_plearnd_admin ผ่าน PlearndAdmin table
        if ($request->has('is_plearnd_admin')) {
            if ($request->boolean('is_plearnd_admin')) {
                PlearndAdmin::firstOrCreate(['user_id' => $user->id]);
            } else {
                PlearndAdmin::where('user_id', $user->id)->delete();
            }
        }

        // Update roles
        if ($request->has('roles') && is_array($request->roles)) {
            $roleIds = Role::whereIn('name', array_map('strtoupper', $request->roles))->pluck('id');
            $user->roles()->sync($roleIds);
        } elseif ($request->has('role')) {
            $role = Role::where('name', strtoupper($request->role))->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตผู้ใช้สำเร็จ',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * GET /api/admin/users/{id}/delete-impact
     * ตรวจสอบผลกระทบก่อนลบ
     */
    public function getDeleteImpact(int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
        }

        if ($user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบ Super Admin ได้'], 403);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบบัญชีตัวเองได้'], 403);
        }

        $impact = $this->impactService->getImpact($user);

        return response()->json([
            'success' => true,
            'data' => $impact,
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Soft delete + anonymize user
     */
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
        }

        if ($user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบ Super Admin ได้'], 403);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบบัญชีตัวเองได้'], 403);
        }

        $validated = request()->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $result = $this->deletionService->softDelete(
                user: $user,
                deletedBy: auth()->user(),
                reason: $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['impact_summary'],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบได้ กรุณาจัดการปัญหาก่อน',
                'blockers' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/users/{id}/restore
     * Restore user ที่ถูก soft delete
     */
    public function restoreUser(int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
        }

        if (! $user->trashed()) {
            return response()->json(['success' => false, 'message' => 'ผู้ใช้นี้ไม่ได้ถูกปิดใช้งาน'], 422);
        }

        $this->deletionService->restore($user, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'กู้คืนบัญชีสำเร็จ หมายเหตุ: email/ชื่อถูก anonymize ไปแล้ว กรุณาอัพเดตผ่านหน้า Edit',
        ]);
    }

    /**
     * POST /api/admin/users/bulk-delete
     * Soft delete หลายคนพร้อมกัน (ไม่รวม Super Admin และตัวเอง)
     */
    public function bulkDelete(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1|max:50',
            'user_ids.*' => 'integer|exists:users,id',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $currentUserId = auth()->id();
        $deletedBy = auth()->user();
        $skipped = [];
        $deleted = 0;

        foreach ($request->user_ids as $id) {
            if ($id === $currentUserId) {
                $skipped[] = ['id' => $id, 'reason' => 'ไม่สามารถลบบัญชีตัวเองได้'];

                continue;
            }

            $user = User::find($id);
            if (! $user) {
                $skipped[] = ['id' => $id, 'reason' => 'ไม่พบผู้ใช้'];

                continue;
            }

            if ($user->isSuperAdmin()) {
                $skipped[] = ['id' => $id, 'reason' => 'ไม่สามารถลบ Super Admin ได้'];

                continue;
            }

            try {
                $this->deletionService->softDelete(
                    user: $user,
                    deletedBy: $deletedBy,
                    reason: $request->reason
                );
                $deleted++;
            } catch (\Exception $e) {
                $skipped[] = ['id' => $id, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "ลบผู้ใช้สำเร็จ {$deleted} คน".(count($skipped) ? ', ข้ามไป '.count($skipped).' คน' : ''),
            'deleted_count' => $deleted,
            'skipped' => $skipped,
        ]);
    }

    public function bulkVerify(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1|max:100',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $count = User::whereIn('id', $request->user_ids)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "ยืนยันบัญชีสำเร็จ {$count} คน",
            'verified_count' => $count,
        ]);
    }

    /**
     * Verify user's email
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลได้รับการยืนยันแล้ว',
            ], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'ยืนยันอีเมลสำเร็จ',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Unverify user's email
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unverifyEmail($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], 404);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลยังไม่ได้รับการยืนยัน',
            ], 400);
        }

        $user->email_verified_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการยืนยันอีเมลสำเร็จ',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Toggle user ban status
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBan($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้',
            ], 404);
        }

        // Prevent banning super admins
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแบนบัญชี Super Admin ได้',
            ], 403);
        }

        $user->is_banned = ! ($user->is_banned ?? false);
        $user->save();

        $action = $user->is_banned ? 'แบน' : 'ปลดแบน';

        return response()->json([
            'success' => true,
            'message' => "{$action}ผู้ใช้สำเร็จ",
            'data' => [
                'is_banned' => $user->is_banned,
            ],
        ]);
    }
}
