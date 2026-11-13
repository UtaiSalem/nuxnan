<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCouponRequest — สร้างคูปองจากหน้าแอดมิน (POST /api/admin/coupons)
 *
 * คูปองเป็นบัตรกำนัลใช้ครั้งเดียว (แต้ม หรือ เงินในกระเป๋า) มูลค่าหักจากยอดของแอดมินผู้สร้างเอง
 * (CouponService::createPointsCoupon / createWalletCoupon) — ไม่มีการสร้างมูลค่าใหม่
 * รหัสคูปองสร้างให้อัตโนมัติ (ตัวเลข 8 หลัก) จึงไม่รับรหัสจากผู้ใช้
 */
class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        // สิทธิ์ตรวจที่ middleware: admin + plearnd_admin + permission:coupon-create
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // แต้มเป็นจำนวนเต็ม · เงินทศนิยมไม่เกิน 2 ตำแหน่ง
        $amountPrecision = $this->input('type') === 'points' ? 'integer' : 'decimal:0,2';

        return [
            'type' => ['required', Rule::in(['points', 'wallet'])],
            'amount' => ['required', 'numeric', $amountPrecision, 'min:1', 'max:1000000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'ประเภทคูปอง',
            'amount' => 'มูลค่าต่อใบ',
            'quantity' => 'จำนวนใบ',
            'description' => 'คำอธิบาย',
            'expires_in_days' => 'อายุคูปอง (วัน)',
        ];
    }
}
