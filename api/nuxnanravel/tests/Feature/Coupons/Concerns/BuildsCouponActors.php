<?php

namespace Tests\Feature\Coupons\Concerns;

use App\Models\Coupon;
use App\Models\Permission;
use App\Models\PlearndAdmin;
use App\Models\Role;
use App\Models\User;
use App\Services\CouponService;

/**
 * ตัวละครที่ใช้ในเทสต์คูปองแอดมิน
 *
 * - platformAdmin(): role ADMIN (มี coupon-*) + plearnd_admins + verified → ผ่านทุกด่าน
 * - vendor(): role VENDOR (มี coupon-list, coupon-create ตาม AdminRbacSeeder) แต่ไม่ใช่ Plearnd admin
 * - plearndWithoutCouponPermission(): Plearnd admin + role MODERATOR (ไม่มีสิทธิ์ coupon-*)
 * - member(): ผู้ใช้ทั่วไป สำหรับแลกคูปอง
 */
trait BuildsCouponActors
{
    protected function grantRole(User $user, string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        $permissionIds = collect($permissions)
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name], ['display_name' => $name, 'group' => 'coupons'])->id)
            ->all();

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->assignRole($roleName);
    }

    protected function platformAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['pp' => 1000, 'wallet' => 500], $attributes));
        $this->grantRole($user, 'ADMIN', ['coupon-list', 'coupon-create', 'coupon-edit', 'coupon-delete']);
        PlearndAdmin::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    protected function vendor(): User
    {
        $user = User::factory()->create(['pp' => 1000, 'wallet' => 500]);
        $this->grantRole($user, 'VENDOR', ['coupon-list', 'coupon-create']);

        return $user->fresh();
    }

    protected function plearndWithoutCouponPermission(): User
    {
        $user = User::factory()->create(['pp' => 1000, 'wallet' => 500]);
        $this->grantRole($user, 'MODERATOR', ['user-list']);
        PlearndAdmin::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    protected function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['pp' => 0, 'wallet' => 0], $attributes));
    }

    /**
     * คูปองที่มีอยู่แล้วในระบบ (ไม่ผ่าน service — ไม่หักยอดผู้สร้าง) ใช้ทดสอบ list/show/update/delete
     */
    protected function makeCoupon(User $creator, array $attributes = []): Coupon
    {
        return Coupon::create(array_merge([
            'user_id' => $creator->id,
            'coupon_code' => app(CouponService::class)->generateCouponCode(),
            'coupon_type' => 'points',
            'amount' => 100,
            'status' => 'active',
            'description' => null,
            'expires_at' => null,
        ], $attributes));
    }

    /**
     * POST /api/admin/coupons สร้างไฟล์ QR จริงใน storage/app/public/qr-codes — ลบทิ้งหลังเทสต์
     */
    protected function deleteGeneratedQrCodes(): void
    {
        foreach (Coupon::pluck('coupon_code') as $code) {
            $path = storage_path('app/public/qr-codes/'.$code.'.svg');
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
