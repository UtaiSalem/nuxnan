<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\AdminPointsController;
use App\Http\Controllers\Api\AdminWalletController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for Super Admin panel to manage users, settings, etc.
| All routes require authentication and super-admin middleware.
|
*/

Route::middleware(['auth:api', 'super-admin'])->prefix('admin')->group(function () {
    // User Management
    Route::get('/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{id}', [AdminController::class, 'show'])->name('admin.users.show');
    Route::put('/users/{id}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    
    // Verify Email (Admin can verify on behalf of user)
    Route::post('/users/{id}/verify-email', function ($id) {
        $user = \App\Models\User::findOrFail($id);
        
        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลของผู้ใช้นี้ได้รับการยืนยันแล้ว'
            ], 400);
        }
        
        $user->email_verified_at = now();
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'ยืนยันอีเมลสำเร็จ',
            'data' => $user
        ]);
    })->name('admin.users.verify-email');
    
    // Unverify Email (Admin can revoke verification)
    Route::post('/users/{id}/unverify-email', function ($id) {
        $user = \App\Models\User::findOrFail($id);
        
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลของผู้ใช้นี้ยังไม่ได้รับการยืนยัน'
            ], 400);
        }
        
        $user->email_verified_at = null;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการยืนยันอีเมลสำเร็จ',
            'data' => $user
        ]);
    })->name('admin.users.unverify-email');
    
    // Dashboard Stats
    // Dashboard Stats
    Route::get('/stats', function () {
        // Calculate revenue (deposits + course purchases)
        // For now using deposits as primary revenue indicator until course purchase transaction type is confirmed
        $revenue = \App\Models\WalletTransaction::whereIn('transaction_type', ['deposit', 'purchase'])
            ->where('status', 'completed')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => \App\Models\User::count(),
                'active_users' => \App\Models\User::whereNotNull('email_verified_at')->count(),
                'new_users_today' => \App\Models\User::whereDate('created_at', today())->count(),
                'new_users_week' => \App\Models\User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_courses' => \App\Models\Course::count(),
                'total_academies' => \App\Models\Academy::count(),
                'total_revenue' => $revenue,
                // Calculate growth (mock logic for now, or compare with last month)
                'users_growth' => 12, // Example fixed value or implement comparison logic
                'courses_growth' => 5,
                'academies_growth' => 2,
                'revenue_growth' => 8,
            ]
        ]);
    })->name('admin.stats');

    // Dashboard Recent Activities (Aggregated)
    Route::get('/dashboard/activities', function () {
        $activities = collect();

        // 1. New Users
        $users = \App\Models\User::latest()
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'type' => 'user',
                    'user' => $user->name,
                    'action' => 'สมัครสมาชิกใหม่',
                    'target' => '',
                    'timestamp' => $user->created_at,
                    'icon' => 'fluent:person-add-24-regular',
                    'color' => 'text-blue-500'
                ];
            });
        $activities = $activities->merge($users);

        // 2. New Courses
        $courses = \App\Models\Course::with('creator')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => 'course_' . $course->id,
                    'type' => 'course',
                    'user' => $course->creator->name ?? 'Unknown',
                    'action' => 'สร้างคอร์สใหม่',
                    'target' => $course->title,
                    'timestamp' => $course->created_at,
                    'icon' => 'fluent:hat-graduation-24-regular',
                    'color' => 'text-green-500'
                ];
            });
        $activities = $activities->merge($courses);

        // 3. New Academies
        $academies = \App\Models\Academy::with('owner')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($academy) {
                return [
                    'id' => 'academy_' . $academy->id,
                    'type' => 'academy',
                    'user' => $academy->owner->name ?? 'Unknown',
                    'action' => 'สร้างอะคาเดมีใหม่',
                    'target' => $academy->name,
                    'timestamp' => $academy->created_at,
                    'icon' => 'fluent:building-24-regular',
                    'color' => 'text-purple-500'
                ];
            });
        $activities = $activities->merge($academies);

        // 4. Wallet Transactions (Deposits only for significance)
        $transactions = \App\Models\WalletTransaction::with('user')
            ->where('transaction_type', 'deposit')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => 'tx_' . $tx->id,
                    'type' => 'transaction',
                    'user' => $tx->user->name ?? 'Unknown',
                    'action' => 'เติมเงิน',
                    'target' => '฿' . number_format($tx->amount),
                    'timestamp' => $tx->created_at,
                    'icon' => 'fluent:wallet-24-regular',
                    'color' => 'text-yellow-500'
                ];
            });
        $activities = $activities->merge($transactions);

        // Sort by timestamp desc and take top 10
        $sorted = $activities->sortByDesc('timestamp')->values()->take(10);

        // Format time for frontend
        $formatted = $sorted->map(function ($item) {
            $item['time'] = \Carbon\Carbon::parse($item['timestamp'])->diffForHumans();
            unset($item['timestamp']); // cleanup
            return $item;
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    })->name('admin.dashboard.activities');

    // Dashboard Top Courses
    Route::get('/dashboard/top-courses', function () {
        // Sort by enrollments (assuming enrollments count or member relationship)
        // If enrolled_students column exists and is maintained:
        $courses = \App\Models\Course::withCount('members')
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

    // Courses Management
    Route::get('/courses', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Course::with(['creator', 'category']);
        
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $courses = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        return response()->json(['success' => true, 'data' => $courses]);
    })->name('admin.courses.index');
    
    Route::post('/courses', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,pending,published,archived',
        ]);
        
        $course = \App\Models\Course::create([
            ...$validated,
            'creator_id' => auth()->id(),
        ]);
        
        return response()->json(['success' => true, 'data' => $course], 201);
    })->name('admin.courses.store');
    
    Route::get('/courses/{id}', function ($id) {
        $course = \App\Models\Course::with(['creator', 'category'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $course]);
    })->name('admin.courses.show');
    
    Route::put('/courses/{id}', function (\Illuminate\Http\Request $request, $id) {
        $course = \App\Models\Course::findOrFail($id);
        $course->update($request->all());
        return response()->json(['success' => true, 'data' => $course]);
    })->name('admin.courses.update');
    
    Route::delete('/courses/{id}', function ($id) {
        $course = \App\Models\Course::findOrFail($id);
        $course->delete();
        return response()->json(['success' => true, 'message' => 'Course deleted']);
    })->name('admin.courses.destroy');

    // Academies Management
    Route::get('/academies', function (\Illuminate\Http\Request $request) {
        $academies = \App\Models\Academy::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        return response()->json(['success' => true, 'data' => $academies]);
    })->name('admin.academies.index');
    
    Route::post('/academies', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $academy = \App\Models\Academy::create([
            ...$validated,
            'owner_id' => auth()->id(),
        ]);
        
        return response()->json(['success' => true, 'data' => $academy], 201);
    })->name('admin.academies.store');
    
    Route::get('/academies/{id}', function ($id) {
        $academy = \App\Models\Academy::with('owner')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $academy]);
    })->name('admin.academies.show');
    
    Route::put('/academies/{id}', function (\Illuminate\Http\Request $request, $id) {
        $academy = \App\Models\Academy::findOrFail($id);
        $academy->update($request->all());
        return response()->json(['success' => true, 'data' => $academy]);
    })->name('admin.academies.update');
    
    Route::delete('/academies/{id}', function ($id) {
        $academy = \App\Models\Academy::findOrFail($id);
        $academy->delete();
        return response()->json(['success' => true, 'message' => 'Academy deleted']);
    })->name('admin.academies.destroy');

    // Coupons Management
    Route::get('/coupons', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Coupon::with(['creator', 'usedBy']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $coupons = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        return response()->json(['success' => true, 'data' => $coupons]);
    })->name('admin.coupons.index');
    
    Route::post('/coupons', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:points,wallet',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);
        
        $coupon = \App\Models\Coupon::create([
            ...$validated,
            'creator_id' => auth()->id(),
            'status' => 'unused',
        ]);
        
        return response()->json(['success' => true, 'data' => $coupon], 201);
    })->name('admin.coupons.store');
    
    Route::get('/coupons/{id}', function ($id) {
        $coupon = \App\Models\Coupon::with(['creator', 'usedBy'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $coupon]);
    })->name('admin.coupons.show');
    
    Route::put('/coupons/{id}', function (\Illuminate\Http\Request $request, $id) {
        $coupon = \App\Models\Coupon::findOrFail($id);
        $coupon->update($request->all());
        return response()->json(['success' => true, 'data' => $coupon]);
    })->name('admin.coupons.update');
    
    Route::delete('/coupons/{id}', function ($id) {
        $coupon = \App\Models\Coupon::findOrFail($id);
        $coupon->delete();
        return response()->json(['success' => true, 'message' => 'Coupon deleted']);
    })->name('admin.coupons.destroy');

    // Points Transactions
    Route::get('/points-transactions', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\PointsTransaction::with(['user', 'targetUser']);
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json(['success' => true, 'data' => $transactions]);
    })->name('admin.points-transactions.index');

    // Wallet Transactions
    Route::get('/wallet-transactions', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\WalletTransaction::with(['user']);
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json(['success' => true, 'data' => $transactions]);
    })->name('admin.wallet-transactions.index');

    // Activities Log
    Route::get('/activities', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\ActivityLog::with('user');
        
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

    // Content Management
    Route::get('/content', function (\Illuminate\Http\Request $request) {
        // Return combined content from different sources
        return response()->json([
            'success' => true,
            'data' => [
                'data' => [],
                'total' => 0
            ]
        ]);
    })->name('admin.content.index');
});
