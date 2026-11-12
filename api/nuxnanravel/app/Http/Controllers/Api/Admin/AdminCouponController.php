<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\Admin\AdminCouponResource;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Services\AuditLogService;
use App\Services\CouponService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * จัดการคูปองจากหน้าแอดมิน (/api/admin/coupons)
 *
 * คูปอง = บัตรกำนัลใช้ครั้งเดียว (แต้ม หรือ เงินในกระเป๋า) แลกผ่าน POST /api/coupons/redeem
 * - สร้าง: หักมูลค่าจากยอดของแอดมินผู้สร้างเอง (เจ้าของโปรเจคเคาะ 2026-11-12 — ไม่สร้างมูลค่าใหม่)
 * - ลบ: = ยกเลิก + คืนยอดให้ผู้สร้าง ไม่ลบแถวจริง (เก็บเป็นประวัติคู่กับ ledger)
 * - สิทธิ์: route ติด admin + plearnd_admin + permission:coupon-* (ดู routes/admin/admin.php)
 */
class AdminCouponController extends Controller
{
    private const STATUSES = ['active', 'redeemed', 'expired', 'cancelled'];

    public function __construct(
        private readonly CouponService $couponService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([...self::STATUSES, 'all'])],
            'type' => ['nullable', Rule::in(['points', 'wallet', 'all'])],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Coupon::query()->with(['user.plearndAdmin', 'redeemedBy']);

        $this->applyStatusFilter($query, $validated['status'] ?? null);

        if (! empty($validated['type']) && $validated['type'] !== 'all') {
            $query->where('coupon_type', $validated['type']);
        }

        if (! empty($validated['search'])) {
            $term = '%'.addcslashes($validated['search'], '\\%_').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('coupon_code', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('user', fn (Builder $u) => $u->where(
                        fn (Builder $w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term)
                    ));
            });
        }

        $coupons = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 12);

        return response()->json([
            'success' => true,
            'data' => AdminCouponResource::collection($coupons)->response()->getData(true),
            'summary' => $this->summary(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::with(['user.plearndAdmin', 'redeemedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminCouponResource($coupon),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $admin = $request->user();
        $validated = $request->validated();
        $quantity = (int) ($validated['quantity'] ?? 1);
        $description = $validated['description'] ?? null;
        $expiresInDays = isset($validated['expires_in_days']) ? (int) $validated['expires_in_days'] : null;

        $result = $validated['type'] === 'points'
            ? $this->couponService->createPointsCoupon($admin, (float) $validated['amount'], $description, $expiresInDays, $quantity)
            : $this->couponService->createWalletCoupon($admin, (float) $validated['amount'], $description, $expiresInDays, $quantity);

        if (! $result['success']) {
            // ยอดของแอดมินไม่พอ / หักยอดไม่สำเร็จ — ไม่มีคูปองถูกสร้าง (ทั้งหมดอยู่ใน transaction เดียว)
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => 'INSUFFICIENT_BALANCE',
            ], 422);
        }

        $coupons = collect($result['coupons']);

        // QR ใช้ตอนพิมพ์คูปอง (GET /api/coupons/{id}/print) — สร้างไม่สำเร็จไม่ควรทำให้คูปองที่จ่ายเงินแล้วหาย
        foreach ($coupons as $coupon) {
            try {
                $this->couponService->generateQRCode($coupon);
            } catch (\Throwable $e) {
                Log::warning('Admin coupon QR generation failed', ['coupon_id' => $coupon->id, 'error' => $e->getMessage()]);
            }
        }

        $this->auditLog->log(AuditLog::ACTION_CREATED, $coupons->first(), null, [
            'coupon_type' => $validated['type'],
            'amount' => $validated['amount'],
            'quantity' => $quantity,
            'total_amount' => $result['total_amount'],
            'coupon_ids' => $coupons->pluck('id')->all(),
        ], 'coupons', ['source' => 'admin_panel']);

        $coupons = Coupon::with(['user.plearndAdmin', 'redeemedBy'])
            ->whereIn('id', $coupons->pluck('id'))
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'coupons' => AdminCouponResource::collection($coupons),
                'count' => $coupons->count(),
                'total_amount' => $result['total_amount'],
            ],
        ], 201);
    }

    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        if ($coupon->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'แก้ไขได้เฉพาะคูปองที่ยังไม่ถูกแลกหรือยกเลิก',
                'error_code' => 'COUPON_NOT_EDITABLE',
            ], 409);
        }

        $validated = $request->validated();
        $old = $coupon->only(array_keys($validated));

        $coupon->update($validated);

        if ($validated !== []) {
            $this->auditLog->log(AuditLog::ACTION_UPDATED, $coupon, $old, $coupon->only(array_keys($validated)), 'coupons', ['source' => 'admin_panel']);
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกคูปองแล้ว',
            'data' => new AdminCouponResource($coupon->fresh(['user.plearndAdmin', 'redeemedBy'])),
        ]);
    }

    /**
     * "ลบ" = ยกเลิกคูปองที่ยังไม่ถูกแลก แล้วคืนยอดให้ผู้สร้าง · แถวคูปองยังอยู่เป็นประวัติ
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $result = $this->couponService->cancelCouponByAdmin($coupon, $request->user());

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'],
            ], 409);
        }

        $this->auditLog->log(AuditLog::ACTION_DELETED, $result['coupon'], ['status' => $coupon->status], [
            'status' => 'cancelled',
            'refunded_to_user_id' => $coupon->user_id,
            'refunded_amount' => $coupon->amount,
            'coupon_type' => $coupon->coupon_type,
        ], 'coupons', ['source' => 'admin_panel']);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new AdminCouponResource($result['coupon']->fresh(['user.plearndAdmin', 'redeemedBy'])),
        ]);
    }

    /**
     * ตัวกรองสถานะตามที่ผู้ใช้เห็นจริง: คูปอง active ที่เลยวันหมดอายุนับเป็น "หมดอายุ"
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        $now = Carbon::now();

        match ($status) {
            'active' => $query->where('status', 'active')
                ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now)),
            'expired' => $query->where(fn (Builder $q) => $q->where('status', 'expired')
                ->orWhere(fn (Builder $q2) => $q2->where('status', 'active')->where('expires_at', '<=', $now))),
            'redeemed', 'cancelled' => $query->where('status', $status),
            default => null,
        };
    }

    /**
     * ภาพรวมคูปองทั้งระบบ — ยอดค้าง (active ยังไม่หมดอายุ) คือมูลค่าที่ยังแลกได้อยู่
     */
    private function summary(): array
    {
        $outstanding = Coupon::query();
        $this->applyStatusFilter($outstanding, 'active');

        $outstanding = $outstanding
            ->selectRaw('coupon_type, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('coupon_type')
            ->get()
            ->keyBy('coupon_type');

        return [
            'active_count' => (int) $outstanding->sum('total_count'),
            'active_points' => (string) ($outstanding->get('points')?->total_amount ?? '0'),
            'active_wallet' => (string) ($outstanding->get('wallet')?->total_amount ?? '0'),
            'redeemed_count' => Coupon::where('status', 'redeemed')->count(),
        ];
    }
}
