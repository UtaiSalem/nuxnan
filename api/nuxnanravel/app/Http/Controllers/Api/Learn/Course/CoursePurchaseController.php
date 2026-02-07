<?php

namespace App\Http\Controllers\Api\Learn\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CoursePurchaseController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Check if user can purchase a course
     * Returns pricing and balance info
     */
    public function checkPurchase(Course $course): JsonResponse
    {
        $user = auth()->user();
        
        // Calculate price with discount
        $originalPrice = $course->tuition_fees ?? $course->price ?? 0;
        $discount = $course->discount ?? 0;
        $finalPrice = $originalPrice;
        
        if ($originalPrice > 0 && $discount > 0) {
            $finalPrice = $originalPrice - ($originalPrice * $discount / 100);
        }
        
        $balance = $user->wallet ?? 0;
        $canPurchase = $balance >= $finalPrice;
        $isFree = $finalPrice == 0;
        
        // Check if already a member
        $isMember = $course->courseMembers()
            ->where('user_id', $user->id)
            ->exists();
        
        // Check if already purchased
        $hasPurchased = $this->walletService->hasPurchased($user, $course);

        return response()->json([
            'success' => true,
            'can_purchase' => $canPurchase,
            'original_price' => $originalPrice,
            'discount_percent' => $discount,
            'discount_amount' => $originalPrice - $finalPrice,
            'final_price' => $finalPrice,
            'balance' => $balance,
            'shortfall' => $canPurchase ? 0 : $finalPrice - $balance,
            'is_free' => $isFree,
            'is_member' => $isMember,
            'has_purchased' => $hasPurchased,
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
        
        $purchases = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->latest()
            ->paginate($perPage);
        
        // Enrich with course data
        $purchases->getCollection()->transform(function ($transaction) {
            $courseId = $transaction->metadata['course_id'] ?? null;
            if ($courseId) {
                $course = Course::find($courseId);
                if ($course) {
                    $transaction->course = [
                        'id' => $course->id,
                        'name' => $course->name,
                        'cover' => $course->cover_url,
                    ];
                }
            }
            return $transaction;
        });

        return response()->json([
            'success' => true,
            'purchases' => $purchases->items(),
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
        
        // Get courses owned by this user
        $courseIds = Course::where('user_id', $user->id)->pluck('id');
        
        // Get income transactions
        $incomeTransactions = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'course_income')
            ->where('status', 'completed');
        
        // Apply date filters
        if ($request->has('from')) {
            $incomeTransactions->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $incomeTransactions->whereDate('created_at', '<=', $request->to);
        }
        
        $transactions = $incomeTransactions->get();
        
        // Calculate totals
        $totalRevenue = $transactions->sum('amount');
        $totalSales = $transactions->count();
        
        // Group by course
        $salesByCourse = $transactions->groupBy(function ($t) {
            return $t->metadata['course_id'] ?? 'unknown';
        })->map(function ($group, $courseId) {
            $course = Course::find($courseId);
            return [
                'course_id' => $courseId,
                'course_name' => $course->name ?? 'Unknown',
                'total_sales' => $group->count(),
                'total_revenue' => $group->sum('amount'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'analytics' => [
                'total_revenue' => $totalRevenue,
                'total_sales' => $totalSales,
                'sales_by_course' => $salesByCourse,
            ],
            'transactions' => $transactions->take(50),
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
        if (!$course->isAdmin(auth()->user()) && $course->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์คืนเงิน',
            ], 403);
        }

        $buyerUser = \App\Models\User::find($request->user_id);
        
        // Find the original purchase
        $originalPurchase = WalletTransaction::where('user_id', $buyerUser->id)
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereJsonContains('metadata->course_id', $course->id)
            ->first();

        if (!$originalPurchase) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบประวัติการซื้อ',
            ], 404);
        }

        $amount = $originalPurchase->metadata['final_price'] ?? $originalPurchase->amount;

        try {
            $refundTransaction = $this->walletService->refundCoursePurchase(
                $buyerUser,
                $course,
                $amount,
                $request->reason
            );

            // Remove membership
            $course->courseMembers()->where('user_id', $buyerUser->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'คืนเงินสำเร็จ',
                'refund_amount' => $amount,
                'transaction_id' => $refundTransaction->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'คืนเงินล้มเหลว: ' . $e->getMessage(),
            ], 400);
        }
    }
}
