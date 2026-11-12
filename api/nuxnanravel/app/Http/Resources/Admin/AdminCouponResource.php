<?php

namespace App\Http\Resources\Admin;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * รูปแบบคูปองที่ส่งให้หน้าแอดมิน (/nuxnan-admin/coupons)
 *
 * ใช้ resource แทน toArray() ของ model เพราะ relation redeemedBy จะถูก serialize เป็น key
 * `redeemed_by` ทับคอลัมน์ id ชื่อเดียวกัน — ที่นี่แยกเป็น `creator` / `redeemer` ชัดเจน
 *
 * @mixin Coupon
 */
class AdminCouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Coupon $coupon */
        $coupon = $this->resource;

        // status ใน DB ยังเป็น active แม้เลยวันหมดอายุ (ไม่มี job เปลี่ยนสถานะ) → ใช้ display_status ในหน้าจอ
        $displayStatus = $coupon->status === 'active' && $coupon->isExpired() ? 'expired' : $coupon->status;

        return [
            'id' => $coupon->id,
            'coupon_code' => $coupon->coupon_code,
            'coupon_type' => $coupon->coupon_type,
            'type_label' => $coupon->type_label,
            'amount' => $coupon->amount,
            'formatted_amount' => $coupon->formatted_amount,
            'status' => $coupon->status,
            'display_status' => $displayStatus,
            'status_label' => match ($displayStatus) {
                'active' => 'ใช้งานได้',
                'redeemed' => 'แลกแล้ว',
                'expired' => 'หมดอายุ',
                'cancelled' => 'ยกเลิกแล้ว',
                default => $displayStatus,
            },
            'description' => $coupon->description,
            'expires_at' => $coupon->expires_at?->toIso8601String(),
            'redeemed_at' => $coupon->redeemed_at?->toIso8601String(),
            'created_at' => $coupon->created_at?->toIso8601String(),
            'updated_at' => $coupon->updated_at?->toIso8601String(),
            'is_expired' => $coupon->isExpired(),
            'can_redeem' => $coupon->canRedeem(),
            // แก้ไข (คำอธิบาย/วันหมดอายุ) ได้เฉพาะคูปองที่ยังไม่ถูกแลก/ยกเลิก
            'can_edit' => $coupon->status === 'active',
            // ยกเลิก+คืนยอดผู้สร้างได้เฉพาะคูปองที่ยังไม่ถูกแลก (ตรงกับ CouponService::cancelCouponByAdmin)
            'can_cancel' => in_array($coupon->status, ['active', 'expired'], true),
            'qr_code_url' => $coupon->qr_code_url,
            'creator' => $this->whenLoaded('user', fn () => $this->person($coupon->user, true)),
            'redeemer' => $this->whenLoaded('redeemedBy', fn () => $this->person($coupon->redeemedBy, false)),
        ];
    }

    private function person(?User $user, bool $withAdminFlag): ?array
    {
        if (! $user) {
            return null;
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
        ];

        if ($withAdminFlag) {
            // คูปองของผู้สร้างที่เป็นแอดมินแพลตฟอร์ม = คูปองโปรโมชันของระบบ (ต่างจากคูปองที่สมาชิกสร้างเอง)
            $data['is_plearnd_admin'] = $user->isPlearndAdmin();
        }

        return $data;
    }
}
