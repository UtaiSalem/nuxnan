<?php

namespace App\Http\Controllers\Api\Learn\Course;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\info\MarketplaceCourseResource;
use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CoursePurchaseService;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseMarketplaceController extends Controller
{
    public function __construct(
        protected CoursePurchaseService $purchaseService,
        protected WalletService $walletService
    ) {}

    /**
     * Get marketplace courses.
     * GET /api/courses/marketplace
     */
    public function index(Request $request): JsonResponse
    {
        $currentUserId = Auth::guard('api')->id();

        $query = Course::where(function ($q) {
            $q->where('is_for_marketplace', true)
                ->orWhere('saleable', true);
        })
            ->with([
                'user',
                'academy',
                'courseMembers' => function ($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                },
                'favorites' => function ($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                },
            ])
            ->withCount([
                'courseLessons',
                'courseAssignments',
                'courseQuizzes',
                'courseMembers as enrolled_students_count' => function ($q) {
                    $q->where('status', 1);
                },
            ]);

        // Text
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        // Categorical
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('education_level')) {
            $query->where('education_level', $request->education_level);
        }
        if ($request->filled('education_year')) {
            $query->where('education_year', $request->education_year);
        }
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Ranges
        if ($request->filled('credit_units_min')) {
            $query->where('credit_units', '>=', $request->credit_units_min);
        }
        if ($request->filled('credit_units_max')) {
            $query->where('credit_units', '<=', $request->credit_units_max);
        }
        if ($request->filled('hours_per_week_min')) {
            $query->where('hours_per_week', '>=', $request->hours_per_week_min);
        }
        if ($request->filled('hours_per_week_max')) {
            $query->where('hours_per_week', '<=', $request->hours_per_week_max);
        }

        // Price
        if ($request->boolean('is_free')) {
            $query->where('price', 0);
        } else {
            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->price_min);
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }
        }

        // Rating
        if ($request->filled('rating_min')) {
            $query->where('rating', '>=', $request->rating_min);
        }

        // Sort
        match ($request->sort ?? 'newest') {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('total_sales', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $courses = $query->paginate($request->per_page ?? 20);

        // Optional: Check academy ownership if academy_id is provided
        if ($request->filled('academy_id')) {
            $academyId = $request->academy_id;
            $ownedCourseIds = Course::where('academy_id', $academyId)
                ->whereNotNull('source_course_id')
                ->whereIn('source_course_id', $courses->pluck('id'))
                ->pluck('source_course_id')
                ->toArray();

            foreach ($courses as $course) {
                $course->owned_by_academy = in_array($course->id, $ownedCourseIds);
            }
        }

        return response()->json([
            'success' => true,
            'data' => MarketplaceCourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * Purchase a course copy.
     * POST /api/courses/{course}/purchase
     */
    public function purchase(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'payment_mode' => 'nullable|in:wallet,points,mixed',
            'academy_id' => 'nullable|exists:academies,id',
        ]);

        try {
            $buyer = Auth::user();
            $academyId = $request->academy_id;

            // Authorization check if purchasing for an academy
            if ($academyId) {
                $academy = Academy::find($academyId);
                $isOwner = $academy->user_id === $buyer->id;
                $isAdmin = AcademyAdmin::where('academy_id', $academyId)
                    ->where('user_id', $buyer->id)
                    ->exists();

                if (! $isOwner && ! $isAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์ดำเนินการในนามโรงเรียนนี้',
                    ], 403);
                }
            }

            $result = $this->purchaseService->purchase($buyer, $course, $request->payment_mode, $academyId);

            return response()->json([
                'success' => true,
                'message' => $result['is_queued']
                    ? 'The course is being cloned. You will be notified when it is ready.'
                    : 'Course purchased successfully.',
                'new_course_id' => $result['new_course'] ? $result['new_course']->id : null,
                'is_queued' => $result['is_queued'],
                'payment' => $result['payment'],
                'purchase_id' => $result['purchase_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if user can purchase a course
     * Returns pricing and balance info
     */
    public function checkPurchase(Course $course): JsonResponse
    {
        $user = auth()->user();
        $POINTS_PER_THB = CoursePurchaseService::POINTS_PER_THB;

        $priceTHB = (float) ($course->price ?? 0);
        $pricePoints = (int) ceil($priceTHB * $POINTS_PER_THB);

        $walletBalance = (float) ($user->wallet ?? 0);
        $pointsBalance = (int) ($user->pp ?? 0);

        $canPayWallet = $walletBalance >= $priceTHB;
        $canPayPoints = $pointsBalance >= $pricePoints;

        // mixed: wallet เท่าที่มี + แปลงส่วนต่างเป็น points
        $thbShortfall = max(0, $priceTHB - $walletBalance);
        $mixedPointsNeeded = (int) ceil($thbShortfall * $POINTS_PER_THB);
        $canPayMixed = $walletBalance > 0
            && $pointsBalance >= $mixedPointsNeeded
            && ! $canPayWallet;

        return response()->json([
            'success' => true,
            'is_free' => $priceTHB <= 0,
            'price_thb' => $priceTHB,
            'price_points' => $pricePoints,
            'exchange_rate' => $POINTS_PER_THB,
            'balance' => [
                'wallet' => $walletBalance,
                'points' => $pointsBalance,
            ],
            'can_pay' => [
                'wallet' => $canPayWallet,
                'points' => $canPayPoints,
                'mixed' => $canPayMixed,
            ],
            'mixed_breakdown' => [
                'wallet_portion' => min($walletBalance, $priceTHB),
                'points_portion' => $mixedPointsNeeded,
            ],
            'has_purchased' => $this->walletService->hasPurchased($user, $course),
            'is_self' => $user->id === $course->user_id,
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'cover' => $course->cover_url,
            ],
        ]);
    }

    /**
     * Get user's course purchase history
     */
    public function getPurchaseHistory(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 20);

        $purchases = CoursePurchase::with(['sourceCourse', 'clonedCourse'])
            ->where('purchase_type', 'marketplace')
            ->where('buyer_id', $user->id)
            ->whereIn('status', ['completed', 'pending_clone', 'paid'])
            ->latest()
            ->paginate($perPage);

        // Map to a consistent format
        $data = collect($purchases->items())->map(function ($purchase) {
            $course = $purchase->sourceCourse;

            return [
                'id' => $purchase->id,
                'status' => $purchase->status,
                'amount_wallet' => $purchase->amount_wallet,
                'amount_points' => $purchase->amount_points,
                'payment_mode' => $purchase->payment_mode,
                'created_at' => $purchase->created_at,
                'course' => $course ? [
                    'id' => $course->id,
                    'name' => $course->name,
                    'cover' => $course->cover_url,
                ] : null,
                'cloned_course_id' => $purchase->cloned_course_id,
            ];
        });

        return response()->json([
            'success' => true,
            'purchases' => $data,
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    /**
     * Get sales analytics for course owners
     */
    public function getSalesAnalytics(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Get sales records
        $salesQuery = CoursePurchase::where('purchase_type', 'marketplace')
            ->where('seller_id', $user->id)
            ->whereIn('status', ['completed', 'pending_clone', 'paid']);

        // Apply date filters
        if ($request->has('from')) {
            $salesQuery->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $salesQuery->whereDate('created_at', '<=', $request->to);
        }

        $sales = $salesQuery->get();

        // Calculate totals
        $totalRevenueTHB = $sales->sum('amount_wallet');
        $totalRevenuePoints = $sales->sum('amount_points');
        $totalSales = $sales->count();

        // Group by course
        $salesByCourse = $sales->groupBy('source_course_id')
            ->map(function ($group, $courseId) {
                $course = Course::find($courseId);

                return [
                    'course_id' => $courseId,
                    'course_name' => $course->name ?? 'Unknown',
                    'total_sales' => $group->count(),
                    'total_revenue_thb' => $group->sum('amount_wallet'),
                    'total_revenue_points' => $group->sum('amount_points'),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'analytics' => [
                'total_revenue_thb' => $totalRevenueTHB,
                'total_revenue_points' => $totalRevenuePoints,
                'total_sales_count' => $totalSales,
                'sales_by_course' => $salesByCourse,
            ],
            'transactions' => $sales->take(50),
        ]);
    }

    /**
     * Refund a course purchase (admin only)
     */
    public function refundPurchase(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        // Check if requester is admin or course owner
        if (! $course->isAdmin(auth()->user()) && $course->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์คืนเงิน',
            ], 403);
        }

        $buyerUser = User::find($request->user_id);

        // Find the record in course_purchases
        $purchase = CoursePurchase::where('buyer_id', $buyerUser->id)
            ->where('source_course_id', $course->id)
            ->whereIn('status', ['completed', 'pending_clone', 'paid'])
            ->latest()
            ->first();

        if (! $purchase) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบประวัติการซื้อ',
            ], 404);
        }

        try {
            \DB::transaction(function () use ($purchase, $buyerUser, $course, $request) {
                // 1. Refund Buyer Wallet
                if ($purchase->wallet_transaction_id) {
                    $this->walletService->refundCoursePurchase(
                        $buyerUser,
                        $course,
                        $purchase->amount_wallet,
                        $request->reason
                    );
                }

                // 2. Refund Buyer Points
                if ($purchase->points_transaction_id) {
                    $pointsService = app(PointsService::class);
                    $pointsService->refund(
                        $buyerUser,
                        $purchase->amount_points,
                        'App\Models\Course',
                        $course->id,
                        'Refund: '.($request->reason ?? 'Course Purchase Refund')
                    );
                }

                // 3. Reverse Seller Income
                if ($purchase->seller_income_transaction_id) {
                    $incomeTransaction = WalletTransaction::find($purchase->seller_income_transaction_id);
                    if ($incomeTransaction && $incomeTransaction->status === 'completed') {
                        $seller = $incomeTransaction->user;
                        $seller->decrement('wallet', $incomeTransaction->amount);
                        $incomeTransaction->update([
                            'status' => 'failed',
                            'description' => $incomeTransaction->description.' (Refunded: '.($request->reason ?? 'Manual Refund').')',
                        ]);
                    }
                }

                // Update purchase status
                $purchase->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);

                // Remove membership
                $course->courseMembers()->where('user_id', $buyerUser->id)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'คืนเงินสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'คืนเงินล้มเหลว: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get marketplace stats for a course.
     * GET /api/courses/marketplace/stats/{course}
     */
    public function stats(Course $course): JsonResponse
    {
        // Only owner or admin can see stats
        if (Auth::id() !== $course->user_id && ! $course->isAdmin(Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $course->total_sales,
                'is_for_marketplace' => $course->is_for_marketplace,
                'price' => $course->price,
            ],
        ]);
    }
}
