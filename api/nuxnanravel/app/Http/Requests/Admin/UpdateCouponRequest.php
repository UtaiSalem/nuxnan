<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateCouponRequest — แก้ไขคูปองจากหน้าแอดมิน (PUT /api/admin/coupons/{id})
 *
 * แก้ได้เฉพาะ description และ expires_at เท่านั้น:
 * - type/amount ห้ามแก้ — มูลค่าถูกหักจากผู้สร้างไปแล้วตอนสร้าง แก้ทีหลังจะทำให้ ledger ไม่ตรง
 * - coupon_code ห้ามแก้ — รหัสถูกแจก/พิมพ์ QR ไปแล้ว
 * - status ห้ามแก้ตรง — การยกเลิกต้องคืนยอดให้ผู้สร้าง จึงต้องผ่าน DELETE (CouponService::cancelCouponByAdmin)
 */
class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        // สิทธิ์ตรวจที่ middleware: admin + plearnd_admin + permission:coupon-edit
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'คำอธิบาย',
            'expires_at' => 'วันหมดอายุ',
        ];
    }
}
