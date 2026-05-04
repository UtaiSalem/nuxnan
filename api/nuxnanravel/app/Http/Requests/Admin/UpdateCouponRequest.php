<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateCouponRequest
 *
 * ใช้แทน $request->all() ใน admin coupon update route
 * ป้องกัน mass assignment (เช่น status แก้เป็น 'used' เพื่อเลี่ยงระบบ,
 * amount แก้เป็นตัวเลขสูง ฯลฯ).
 */
class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $couponId = $this->route('id');

        return [
            'code'        => ['sometimes', 'string', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type'        => ['sometimes', Rule::in(['points', 'wallet'])],
            'amount'      => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'expires_at'  => ['sometimes', 'nullable', 'date'],
            'status'      => ['sometimes', Rule::in(['unused', 'used', 'expired', 'disabled'])],
        ];
    }
}
