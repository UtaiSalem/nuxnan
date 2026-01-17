<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CouponService
{
    protected PointsService $pointsService;
    protected WalletService $walletService;

    public function __construct(PointsService $pointsService, WalletService $walletService)
    {
        $this->pointsService = $pointsService;
        $this->walletService = $walletService;
    }

    /**
     * Generate unique coupon code (numeric only for user convenience).
     */
    public function generateCouponCode(): string
    {
        do {
            // Generate 8-digit numeric code for easier input
            $code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Coupon::where('coupon_code', $code)->exists());

        return $code;
    }

    /**
     * Create coupon from points.
     */
    public function createPointsCoupon(User $user, float $points, ?string $description = null, ?int $expiresInDays = null, int $quantity = 1): array
    {
        return DB::transaction(function () use ($user, $points, $description, $expiresInDays, $quantity) {
            $totalPoints = $points * $quantity;

            // Check if user has enough points
            if ($user->pp < $totalPoints) {
                return [
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ',
                ];
            }

            // Deduct points from user
            $transaction = $this->pointsService->spend(
                $user,
                $totalPoints,
                'coupon_creation',
                null,
                $description ?? "สร้างคูปองแต้ม {$quantity} ใบ",
                ['coupon_type' => 'points', 'quantity' => $quantity]
            );

            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถหักแต้มได้',
                ];
            }

            // Create coupons
            $coupons = [];
            for ($i = 0; $i < $quantity; $i++) {
                $coupon = Coupon::create([
                    'user_id' => $user->id,
                    'coupon_code' => $this->generateCouponCode(),
                    'coupon_type' => 'points',
                    'amount' => $points,
                    'status' => 'active',
                    'description' => $description,
                    'expires_at' => $expiresInDays ? Carbon::now()->addDays($expiresInDays) : null,
                ]);
                $coupons[] = $coupon;

                Log::info('Points coupon created', [
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'amount' => $points,
                    'quantity' => $quantity,
                    'index' => $i + 1,
                ]);
            }

            return [
                'success' => true,
                'message' => $quantity > 1 ? "สร้างคูปอง {$quantity} ใบสำเร็จ" : 'สร้างคูปองสำเร็จ',
                'coupons' => $coupons,
                'total_amount' => $totalPoints,
            ];
        });
    }

    /**
     * Create coupon from wallet.
     */
    public function createWalletCoupon(User $user, float $amount, ?string $description = null, ?int $expiresInDays = null, int $quantity = 1): array
    {
        return DB::transaction(function () use ($user, $amount, $description, $expiresInDays, $quantity) {
            $totalAmount = $amount * $quantity;

            // Check if user has enough wallet balance
            if ($user->wallet < $totalAmount) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                ];
            }

            // Deduct amount from wallet
            $transaction = $this->walletService->withdraw(
                $user,
                $totalAmount,
                'coupon_creation',
                [
                    'bank_name' => 'Internal',
                    'account_number' => 'N/A',
                    'account_name' => 'Coupon Creation',
                ],
                $description ?? "สร้างคูปองเงิน {$quantity} ใบ"
            );

            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถหักเงินได้',
                ];
            }

            // Create coupons
            $coupons = [];
            for ($i = 0; $i < $quantity; $i++) {
                $coupon = Coupon::create([
                    'user_id' => $user->id,
                    'coupon_code' => $this->generateCouponCode(),
                    'coupon_type' => 'wallet',
                    'amount' => $amount,
                    'status' => 'active',
                    'description' => $description,
                    'expires_at' => $expiresInDays ? Carbon::now()->addDays($expiresInDays) : null,
                ]);
                $coupons[] = $coupon;

                Log::info('Wallet coupon created', [
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'amount' => $amount,
                    'quantity' => $quantity,
                    'index' => $i + 1,
                ]);
            }

            return [
                'success' => true,
                'message' => $quantity > 1 ? "สร้างคูปอง {$quantity} ใบสำเร็จ" : 'สร้างคูปองสำเร็จ',
                'coupons' => $coupons,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Redeem coupon by code.
     */
    public function redeemCoupon(User $user, string $couponCode, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($user, $couponCode, $ipAddress, $userAgent) {
            // First, find the coupon by code (any status)
            $coupon = Coupon::where('coupon_code', $couponCode)->first();

            // Check if coupon exists
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบคูปองที่มีรหัสนี้',
                    'error_code' => 'COUPON_NOT_FOUND',
                ];
            }

            // Check if already redeemed
            if ($coupon->status === 'redeemed') {
                return [
                    'success' => false,
                    'message' => 'คูปองนี้ถูกใช้งานไปแล้ว',
                    'error_code' => 'COUPON_ALREADY_REDEEMED',
                    'redeemed_at' => $coupon->redeemed_at,
                ];
            }

            // Check if cancelled
            if ($coupon->status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'คูปองนี้ถูกยกเลิกแล้ว',
                    'error_code' => 'COUPON_CANCELLED',
                ];
            }

            // Check if expired
            if ($coupon->status === 'expired' || $coupon->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'คูปองนี้หมดอายุแล้ว',
                    'error_code' => 'COUPON_EXPIRED',
                    'expires_at' => $coupon->expires_at,
                ];
            }

            // Check if not active
            if ($coupon->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'คูปองนี้ไม่สามารถใช้งานได้',
                    'error_code' => 'COUPON_INACTIVE',
                ];
            }

            // Check if user is trying to redeem their own coupon
            if ($coupon->user_id === $user->id) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถใช้คูปองของตัวเองได้',
                    'error_code' => 'COUPON_OWN',
                ];
            }

            // Redeem based on coupon type
            if ($coupon->coupon_type === 'points') {
                $result = $this->redeemPointsCoupon($coupon, $user);
            } else {
                $result = $this->redeemWalletCoupon($coupon, $user);
            }

            if (!$result['success']) {
                return $result;
            }

            // Mark coupon as redeemed
            $coupon->markAsRedeemed($user);

            // Create redemption record
            CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            Log::info('Coupon redeemed', [
                'coupon_id' => $coupon->id,
                'redeemed_by' => $user->id,
                'amount' => $coupon->amount,
                'type' => $coupon->coupon_type,
            ]);

            return [
                'success' => true,
                'message' => $coupon->coupon_type === 'points' 
                    ? 'รับแต้มสำเร็จ' 
                    : 'รับเงินสำเร็จ',
                'type' => $coupon->coupon_type,
                'amount' => $coupon->amount,
                'new_balance' => $result['new_balance'],
                'coupon' => $coupon,
            ];
        });
    }

    /**
     * Redeem points coupon.
     */
    protected function redeemPointsCoupon(Coupon $coupon, User $user): array
    {
        $transaction = $this->pointsService->earn(
            $user,
            $coupon->amount,
            'coupon_redemption',
            $coupon->id,
            "รับแต้มจากคูปอง {$coupon->coupon_code}",
            ['coupon_code' => $coupon->coupon_code]
        );

        return [
            'success' => true,
            'new_balance' => $transaction->balance_after,
        ];
    }

    /**
     * Redeem wallet coupon.
     */
    protected function redeemWalletCoupon(Coupon $coupon, User $user): array
    {
        $result = $this->walletService->deposit(
            $user,
            $coupon->amount,
            'coupon_redemption',
            $coupon->coupon_code,
            "รับเงินจากคูปอง {$coupon->coupon_code}",
            ['coupon_code' => $coupon->coupon_code]
        );

        return [
            'success' => true,
            'new_balance' => $result->balance_after,
        ];
    }

    /**
     * Cancel coupon.
     */
    public function cancelCoupon(Coupon $coupon, User $user): array
    {
        // Check if user owns the coupon
        if ($coupon->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'ไม่มีสิทธิ์ยกเลิกคูปองนี้',
            ];
        }

        // Check if coupon can be cancelled
        if ($coupon->status !== 'active') {
            return [
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกคูปองนี้ได้',
            ];
        }

        return DB::transaction(function () use ($coupon, $user) {
            // Cancel coupon
            $coupon->cancel();

            // Refund based on coupon type
            if ($coupon->coupon_type === 'points') {
                $this->pointsService->refund(
                    $user,
                    $coupon->amount,
                    'coupon_cancellation',
                    $coupon->id,
                    "คืนแต้มจากการยกเลิกคูปอง {$coupon->coupon_code}",
                    ['coupon_code' => $coupon->coupon_code]
                );
            } else {
                $this->walletService->deposit(
                    $user,
                    $coupon->amount,
                    'coupon_cancellation',
                    $coupon->coupon_code,
                    "คืนเงินจากการยกเลิกคูปอง {$coupon->coupon_code}",
                    ['coupon_code' => $coupon->coupon_code]
                );
            }

            Log::info('Coupon cancelled', [
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'amount' => $coupon->amount,
                'type' => $coupon->coupon_type,
            ]);

            return [
                'success' => true,
                'message' => 'ยกเลิกคูปองสำเร็จ',
            ];
        });
    }

    /**
     * Get user coupons with filters.
     */
    public function getUserCoupons(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $user->coupons();

        // Filter by type
        if (isset($filters['type']) && in_array($filters['type'], ['points', 'wallet'])) {
            $query->where('coupon_type', $filters['type']);
        }

        // Filter by status
        if (isset($filters['status']) && in_array($filters['status'], ['active', 'redeemed', 'expired', 'cancelled'])) {
            $query->where('status', $filters['status']);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Get coupon by ID.
     */
    public function getCouponById(int $id, User $user): ?Coupon
    {
        return $user->coupons()->find($id);
    }

    /**
     * Generate QR code for coupon.
     * Uses Universal QR format: COUPON:code
     */
    public function generateQRCode(Coupon $coupon): string
    {
        // QR data uses Universal format: COUPON:code
        // This allows the scanner to detect the type automatically
        $qrData = 'COUPON:' . $coupon->coupon_code;

        $fileName = 'qr-codes/' . $coupon->coupon_code . '.svg';
        $path = storage_path('app/public/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Generate QR code using simple-qrcode (SVG format - no imagick needed)
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrData);

        // Save SVG file
        file_put_contents($path, $qrCode);

        return $fileName;
    }

    /**
     * Mark expired coupons.
     */
    public function markExpiredCoupons(): int
    {
        $expiredCoupons = Coupon::active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredCoupons as $coupon) {
            if ($coupon->markExpired()) {
                $count++;
            }
        }

        return $count;
    }
}
