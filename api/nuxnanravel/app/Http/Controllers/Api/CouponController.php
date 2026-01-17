<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Create a new coupon.
     */
    public function create(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'type' => 'required|in:points,wallet',
            'amount' => 'required|numeric|min:1',
            'quantity' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string|max:500',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $quantity = $validated['quantity'] ?? 1;

        try {
            if ($validated['type'] === 'points') {
                $result = $this->couponService->createPointsCoupon(
                    $user,
                    $validated['amount'],
                    $validated['description'] ?? null,
                    $validated['expires_in_days'] ?? null,
                    $quantity
                );
            } else {
                $result = $this->couponService->createWalletCoupon(
                    $user,
                    $validated['amount'],
                    $validated['description'] ?? null,
                    $validated['expires_in_days'] ?? null,
                    $quantity
                );
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            // Generate QR codes for all coupons
            $coupons = $result['coupons'];
            foreach ($coupons as $coupon) {
                $this->couponService->generateQRCode($coupon);
                $coupon->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'coupons' => $coupons,
                    'count' => count($coupons),
                    'total_amount' => $result['total_amount'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's coupons.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $filters = [];

        if ($request->has('type') && in_array($request->type, ['points', 'wallet'])) {
            $filters['type'] = $request->type;
        }

        if ($request->has('status') && in_array($request->status, ['active', 'redeemed', 'expired', 'cancelled'])) {
            $filters['status'] = $request->status;
        }

        $coupons = $this->couponService->getUserCoupons($user, $filters);

        return response()->json([
            'success' => true,
            'data' => [
                'coupons' => $coupons,
                'total' => $coupons->count(),
            ],
        ]);
    }

    /**
     * Get coupon details.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $coupon = $this->couponService->getCouponById($id, $user);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'coupon' => $coupon,
            ],
        ]);
    }

    /**
     * Redeem a coupon by code.
     */
    public function redeem(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'coupon_code' => 'required|string|max:32',
        ]);

        try {
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            $result = $this->couponService->redeemCoupon(
                $user,
                $validated['coupon_code'],
                $ipAddress,
                $userAgent
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'type' => $result['type'],
                    'amount' => $result['amount'],
                    'new_balance' => $result['new_balance'],
                    'coupon' => $result['coupon'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ], 400);
        }
    }

    /**
     * Cancel a coupon.
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $coupon = $this->couponService->getCouponById($id, $user);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        try {
            $result = $this->couponService->cancelCoupon($coupon, $user);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get printable coupon data.
     */
    public function print(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $coupon = $this->couponService->getCouponById($id, $user);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'coupon' => $coupon,
                'qr_code_url' => $coupon->qr_code_url,
                'print_data' => [
                    'title' => $coupon->coupon_type === 'points' ? 'คูปองแต้ม' : 'คูปองเงิน',
                    'amount' => $coupon->formatted_amount,
                    'code' => $coupon->coupon_code,
                    'description' => $coupon->description,
                    'created_at' => $coupon->created_at->format('d/m/Y H:i'),
                    'expires_at' => $coupon->expires_at ? $coupon->expires_at->format('d/m/Y H:i') : 'ไม่มีวันหมดอายุ',
                    'status' => $coupon->status_label,
                ],
            ],
        ]);
    }

    /**
     * Get coupon statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $totalCoupons = $user->coupons()->count();
        $activeCoupons = $user->coupons()->active()->count();
        $redeemedCoupons = $user->coupons()->where('status', 'redeemed')->count();
        $cancelledCoupons = $user->coupons()->where('status', 'cancelled')->count();
        $expiredCoupons = $user->coupons()->where('status', 'expired')->count();

        $pointsCoupons = $user->coupons()->points()->count();
        $walletCoupons = $user->coupons()->wallet()->count();

        $totalPointsInCoupons = $user->coupons()->points()->active()->sum('amount');
        $totalWalletInCoupons = $user->coupons()->wallet()->active()->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_coupons' => $totalCoupons,
                'active_coupons' => $activeCoupons,
                'redeemed_coupons' => $redeemedCoupons,
                'cancelled_coupons' => $cancelledCoupons,
                'expired_coupons' => $expiredCoupons,
                'points_coupons' => $pointsCoupons,
                'wallet_coupons' => $walletCoupons,
                'total_points_in_coupons' => $totalPointsInCoupons,
                'total_wallet_in_coupons' => $totalWalletInCoupons,
            ],
        ]);
    }
}
