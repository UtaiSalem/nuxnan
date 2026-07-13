<?php

use App\Http\Controllers\Api\Admin\GamificationRuleLogController;
use App\Http\Controllers\Api\Admin\PointRuleController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminPermissionController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminWalletController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Requests\Admin\StoreAcademyRequest;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateAcademyRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Academy;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (Enhanced)
|--------------------------------------------------------------------------
|
| Routes for Admin panel to manage users, roles, permissions, etc.
| All routes require authentication and admin middleware with granular permissions.
|
| Middleware:
| - admin: Requires SUPER_ADMIN, ADMIN, MODERATOR, or INSTRUCTOR role
| - permission:xxx: Requires specific permission for the action
|
*/

// Health Check (public)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Admin API is running',
        'version' => '2.0.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Auth Routes (public - no middleware)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Protected auth routes
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/refresh', [AdminAuthController::class, 'refresh']);
        Route::get('/me', [AdminAuthController::class, 'me']);
    });
});

// Protected Admin Routes
Route::middleware(['auth:api', 'admin'])->group(function () {

    // =====================================================
    // Dashboard
    // =====================================================
    Route::get('/stats', function () {
        $revenue = WalletTransaction::whereIn('transaction_type', ['deposit', 'purchase'])
            ->where('status', 'completed')
            ->sum('amount');

        $lastMonth = now()->subMonth();
        $usersLastMonth = User::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)->count();
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        $usersGrowth = $usersLastMonth > 0
            ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : 100;

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'pending_users' => User::whereNull('email_verified_at')->count(),
                'new_users_today' => User::whereDate('created_at', today())->count(),
                'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_users_this_month' => $usersThisMonth,
                'total_courses' => Course::count(),
                'total_academies' => Academy::count(),
                'total_revenue' => $revenue,
                'users_growth' => $usersGrowth,
            ],
        ]);
    })->name('admin.stats');

    Route::get('/dashboard/recent-users', function () {
        $users = User::with('roles')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'roles' => $user->roles->pluck('name'),
                    'verified' => $user->email_verified_at !== null,
                    'created_at' => $user->created_at->diffForHumans(),
                ];
            });

        return response()->json(['success' => true, 'data' => $users]);
    });

    Route::get('/dashboard/recent-courses', function () {
        $courses = Course::with('creator')
            ->withCount('members')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'creator' => $course->creator->name ?? 'Unknown',
                    'members_count' => $course->members_count,
                    'price' => $course->price,
                    'created_at' => $course->created_at->diffForHumans(),
                ];
            });

        return response()->json(['success' => true, 'data' => $courses]);
    });

    Route::get('/dashboard/activities', function () {
        $activities = collect();

        // New Users
        $users = User::latest()
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_'.$user->id,
                    'type' => 'user',
                    'user' => $user->name,
                    'action' => 'สมัครสมาชิกใหม่',
                    'target' => '',
                    'timestamp' => $user->created_at,
                    'icon' => 'fluent:person-add-24-regular',
                    'color' => 'text-blue-500',
                ];
            });
        $activities = $activities->merge($users);

        // New Courses
        $courses = Course::with('creator')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => 'course_'.$course->id,
                    'type' => 'course',
                    'user' => $course->creator->name ?? 'Unknown',
                    'action' => 'สร้างคอร์สใหม่',
                    'target' => $course->title,
                    'timestamp' => $course->created_at,
                    'icon' => 'fluent:hat-graduation-24-regular',
                    'color' => 'text-green-500',
                ];
            });
        $activities = $activities->merge($courses);

        // New Academies
        $academies = Academy::with('owner')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($academy) {
                return [
                    'id' => 'academy_'.$academy->id,
                    'type' => 'academy',
                    'user' => $academy->owner->name ?? 'Unknown',
                    'action' => 'สร้างอะคาเดมีใหม่',
                    'target' => $academy->name,
                    'timestamp' => $academy->created_at,
                    'icon' => 'fluent:building-24-regular',
                    'color' => 'text-purple-500',
                ];
            });
        $activities = $activities->merge($academies);

        // Sort and take top 10
        $sorted = $activities->sortByDesc('timestamp')->values()->take(10);
        $formatted = $sorted->map(function ($item) {
            $item['time'] = Carbon::parse($item['timestamp'])->diffForHumans();
            unset($item['timestamp']);

            return $item;
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    })->name('admin.dashboard.activities');

    Route::get('/dashboard/top-courses', function () {
        $courses = Course::withCount('members')
            ->orderBy('members_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($course) {
                $enrollments = $course->members_count;
                $price = $course->price ?? 0;

                return [
                    'id' => $course->id,
                    'name' => $course->title,
                    'enrollments' => $enrollments,
                    'revenue' => $enrollments * $price,
                ];
            });

        return response()->json(['success' => true, 'data' => $courses]);
    })->name('admin.dashboard.top-courses');

    // =====================================================
    // User Management
    // =====================================================
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.users.index');
        Route::get('/datatable', [AdminController::class, 'datatable'])->name('admin.users.datatable');
        Route::get('/statistics', [AdminController::class, 'statistics'])->name('admin.users.statistics');
        Route::post('/', [AdminController::class, 'store'])->middleware('permission:user-create')->name('admin.users.store');

        Route::post('/bulk-verify', [AdminController::class, 'bulkVerify'])
            ->middleware('permission:user-edit')
            ->name('admin.users.bulk-verify');

        Route::post('/bulk-delete', [AdminController::class, 'bulkDelete'])
            ->middleware('permission:user-delete')
            ->name('admin.users.bulk-delete');

        Route::get('/{id}', [AdminController::class, 'show'])->name('admin.users.show');
        Route::put('/{id}', [AdminController::class, 'update'])->middleware('permission:user-edit')->name('admin.users.update');

        // ── DELETE FLOW ──────────────────────────────────────────
        Route::get('/{id}/delete-impact', [AdminController::class, 'getDeleteImpact'])
            ->middleware('permission:user-delete')
            ->name('admin.users.delete-impact');

        Route::delete('/{id}', [AdminController::class, 'destroy'])
            ->middleware('permission:user-delete')
            ->name('admin.users.destroy');

        Route::post('/{id}/restore', [AdminController::class, 'restoreUser'])
            ->middleware('permission:user-delete')
            ->name('admin.users.restore');
        // ─────────────────────────────────────────────────────────

        Route::post('/{id}/verify-email', [AdminController::class, 'verifyEmail'])->middleware('permission:user-edit')->name('admin.users.verify-email');
        Route::post('/{id}/unverify-email', [AdminController::class, 'unverifyEmail'])->middleware('permission:user-edit')->name('admin.users.unverify-email');
        Route::post('/{id}/toggle-ban', [AdminController::class, 'toggleBan'])->middleware('permission:user-edit')->name('admin.users.toggle-ban');
    });

    // =====================================================
    // Role Management (Super Admin only for write operations)
    // =====================================================
    Route::prefix('roles')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/datatable', [AdminRoleController::class, 'datatable'])->name('admin.roles.datatable');
        Route::get('/permissions', [AdminRoleController::class, 'permissions'])->name('admin.roles.permissions');
        Route::post('/', [AdminRoleController::class, 'store'])->middleware('admin:SUPER_ADMIN')->name('admin.roles.store');
        Route::get('/{id}', [AdminRoleController::class, 'show'])->name('admin.roles.show');
        Route::put('/{id}', [AdminRoleController::class, 'update'])->middleware('admin:SUPER_ADMIN')->name('admin.roles.update');
        Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->middleware('admin:SUPER_ADMIN')->name('admin.roles.destroy');
    });

    // =====================================================
    // Permission Management (Super Admin only)
    // =====================================================
    Route::prefix('permissions')->middleware('admin:SUPER_ADMIN')->group(function () {
        Route::get('/', [AdminPermissionController::class, 'index'])->name('admin.permissions.index');
        Route::get('/groups', [AdminPermissionController::class, 'groups'])->name('admin.permissions.groups');
        Route::post('/', [AdminPermissionController::class, 'store'])->name('admin.permissions.store');
        Route::post('/bulk', [AdminPermissionController::class, 'bulkCreate'])->name('admin.permissions.bulk');
        Route::get('/{id}', [AdminPermissionController::class, 'show'])->name('admin.permissions.show');
        Route::put('/{id}', [AdminPermissionController::class, 'update'])->name('admin.permissions.update');
        Route::delete('/{id}', [AdminPermissionController::class, 'destroy'])->name('admin.permissions.destroy');
    });

    // =====================================================
    // Courses Management
    // =====================================================
    Route::prefix('courses')->group(function () {
        Route::get('/', function (Request $request) {
            $query = Course::with(['user'])->withCount('members');

            if ($request->has('search') && $request->search) {
                $query->where('title', 'like', '%'.$request->search.'%');
            }

            $courses = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            // Append cover_url attribute
            $courses->getCollection()->transform(function ($course) {
                $course->append('cover_url');

                return $course;
            });

            return response()->json(['success' => true, 'data' => $courses]);
        })->name('admin.courses.index');

        Route::post('/', function (StoreCourseRequest $request) {
            $course = Course::create([
                ...$request->validated(),
                'creator_id' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'data' => $course], 201);
        })->middleware('permission:course-create')->name('admin.courses.store');

        Route::get('/{id}', function ($id) {
            $course = Course::with(['user'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $course]);
        })->name('admin.courses.show');

        Route::put('/{id}', function (UpdateCourseRequest $request, $id) {
            $course = Course::findOrFail($id);
            // ใช้ validated() แทน all() เพื่อป้องกัน mass assignment
            $course->update($request->validated());

            return response()->json(['success' => true, 'data' => $course]);
        })->middleware('permission:course-edit')->name('admin.courses.update');

        Route::delete('/{id}', function ($id) {
            $course = Course::findOrFail($id);
            $course->delete();

            return response()->json(['success' => true, 'message' => 'ลบคอร์สสำเร็จ']);
        })->middleware('permission:course-delete')->name('admin.courses.destroy');
    });

    // =====================================================
    // Academies Management
    // =====================================================
    Route::prefix('academies')->group(function () {
        Route::get('/', function (Request $request) {
            $academies = Academy::with('owner')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json(['success' => true, 'data' => $academies]);
        })->name('admin.academies.index');

        Route::post('/', function (StoreAcademyRequest $request) {
            $academy = Academy::create([
                ...$request->validated(),
                'owner_id' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'data' => $academy], 201);
        })->middleware('permission:academy-create')->name('admin.academies.store');

        Route::get('/{id}', function ($id) {
            $academy = Academy::with('owner')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $academy]);
        })->name('admin.academies.show');

        Route::put('/{id}', function (UpdateAcademyRequest $request, $id) {
            $academy = Academy::findOrFail($id);
            // ใช้ validated() แทน all() เพื่อป้องกัน mass assignment
            $academy->update($request->validated());

            return response()->json(['success' => true, 'data' => $academy]);
        })->middleware('permission:academy-edit')->name('admin.academies.update');

        Route::delete('/{id}', function ($id) {
            $academy = Academy::findOrFail($id);
            $academy->delete();

            return response()->json(['success' => true, 'message' => 'ลบอะคาเดมีสำเร็จ']);
        })->middleware('permission:academy-delete')->name('admin.academies.destroy');
    });

    // =====================================================
    // Coupons Management
    // =====================================================
    Route::prefix('coupons')->group(function () {
        Route::get('/', function (Request $request) {
            $query = Coupon::with(['creator', 'usedBy']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $coupons = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json(['success' => true, 'data' => $coupons]);
        })->name('admin.coupons.index');

        Route::post('/', function (Request $request) {
            $validated = $request->validate([
                'code' => 'required|string|unique:coupons,code',
                'type' => 'required|in:points,wallet',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'expires_at' => 'nullable|date',
            ]);

            $coupon = Coupon::create([
                ...$validated,
                'creator_id' => auth()->id(),
                'status' => 'unused',
            ]);

            return response()->json(['success' => true, 'data' => $coupon], 201);
        })->middleware('permission:coupon-create')->name('admin.coupons.store');

        Route::get('/{id}', function ($id) {
            $coupon = Coupon::with(['creator', 'usedBy'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $coupon]);
        })->name('admin.coupons.show');

        Route::put('/{id}', function (UpdateCouponRequest $request, $id) {
            $coupon = Coupon::findOrFail($id);
            // ใช้ validated() แทน all() เพื่อป้องกัน mass assignment
            $coupon->update($request->validated());

            return response()->json(['success' => true, 'data' => $coupon]);
        })->middleware('permission:coupon-edit')->name('admin.coupons.update');

        Route::delete('/{id}', function ($id) {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();

            return response()->json(['success' => true, 'message' => 'ลบคูปองสำเร็จ']);
        })->middleware('permission:coupon-delete')->name('admin.coupons.destroy');
    });

    // =====================================================
    // Transactions
    // =====================================================
    Route::get('/points-transactions', function (Request $request) {
        $query = PointsTransaction::with(['user', 'targetUser']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $transactions]);
    })->name('admin.points-transactions.index');

    Route::get('/wallet-transactions', function (Request $request) {
        $query = WalletTransaction::with(['user']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transaction_type') && $request->transaction_type !== 'all') {
            $transactionType = $request->transaction_type === 'withdrawal'
                ? 'withdraw'
                : $request->transaction_type;
            $query->where('transaction_type', $transactionType);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $transactions]);
    })->name('admin.wallet-transactions.index');

    // =====================================================
    // Activities & Content
    // =====================================================
    Route::get('/activities', function (Request $request) {
        $query = ActivityLog::with('user');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $activities]);
    })->name('admin.activities.index');

    Route::get('/content', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'data' => [],
                'total' => 0,
            ],
        ]);
    })->name('admin.content.index');

    // =====================================================
    // Wallet Management
    // =====================================================
    Route::prefix('wallet')->group(function () {
        Route::get('/stats', [AdminWalletController::class, 'stats'])->name('admin.wallet.stats');
        Route::get('/withdrawals/pending', [AdminWalletController::class, 'pendingWithdrawals'])->name('admin.wallet.withdrawals.pending');
        Route::get('/deposit-requests/pending', [AdminWalletController::class, 'pendingDepositRequests'])->name('admin.wallet.deposits.pending');
        Route::get('/withdrawals/{id}', [AdminWalletController::class, 'showWithdrawal'])->whereNumber('id')->name('admin.wallet.withdrawals.show');
        Route::get('/withdrawals/{id}/proof', [AdminWalletController::class, 'downloadWithdrawalProof'])->whereNumber('id')->name('admin.wallet.withdrawals.proof');
        Route::post('/withdrawals/{id}/approve', [AdminWalletController::class, 'approveWithdrawal'])->name('admin.wallet.withdrawals.approve');
        Route::post('/withdrawals/{id}/reject', [AdminWalletController::class, 'rejectWithdrawal'])->name('admin.wallet.withdrawals.reject');
        Route::post('/withdrawals/{id}/process', [AdminWalletController::class, 'processWithdrawal'])->name('admin.wallet.withdrawals.process');
        Route::post('/withdrawals/{id}/paid', [AdminWalletController::class, 'markWithdrawalPaid'])->name('admin.wallet.withdrawals.paid');
        Route::post('/withdrawals/{id}/failed', [AdminWalletController::class, 'markWithdrawalFailed'])->name('admin.wallet.withdrawals.failed');
        Route::post('/deposit-requests/{id}/approve', [AdminWalletController::class, 'approveDepositRequest'])->name('admin.wallet.deposits.approve');
        Route::post('/deposit-requests/{id}/reject', [AdminWalletController::class, 'rejectDepositRequest'])->name('admin.wallet.deposits.reject');
    });

    // =====================================================
    // System Settings (Super Admin only)
    // =====================================================
    Route::prefix('settings')->middleware('admin:SUPER_ADMIN')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'app_name' => config('app.name'),
                    'app_url' => config('app.url'),
                    'app_env' => config('app.env'),
                    'app_debug' => config('app.debug'),
                ],
            ]);
        });
    });

    // =====================================================
    // Audit Logs
    // =====================================================
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/actions', [AuditLogController::class, 'getActions'])->name('admin.audit-logs.actions');
        Route::get('/modules', [AuditLogController::class, 'getModules'])->name('admin.audit-logs.modules');
        Route::get('/summary', [AuditLogController::class, 'summary'])->name('admin.audit-logs.summary');
        Route::get('/entity', [AuditLogController::class, 'getEntityLogs'])->name('admin.audit-logs.entity');
        Route::get('/my-logs', [AuditLogController::class, 'myLogs'])->name('admin.audit-logs.my-logs');
        Route::get('/export', [AuditLogController::class, 'export'])->name('admin.audit-logs.export');
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');

        // Cleanup (Super Admin only)
        Route::delete('/cleanup', [AuditLogController::class, 'cleanup'])
            ->middleware('admin:SUPER_ADMIN')
            ->name('admin.audit-logs.cleanup');
    });

    // =====================================================
    // Gamification & XP
    // =====================================================
    Route::prefix('gamification')->group(function () {
        Route::get('/logs', [GamificationRuleLogController::class, 'index'])->name('admin.gamification.logs');
        Route::apiResource('point-rules', PointRuleController::class);
        Route::patch('point-rules/{pointRule}/toggle', [PointRuleController::class, 'toggle'])->name('admin.point-rules.toggle');
    });
});
