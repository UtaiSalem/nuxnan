<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

/**
 * สิ่งเดียวที่คลาสนี้ทำจริงคือ "แจก role เริ่มต้นให้บัญชีที่เพิ่งสร้าง"
 * ซึ่งเป็นจุดร่วมของสองทางสมัคร: `AuthController::register()` และ `SocialAuthController`
 *
 * เดิมคลาสนี้มีอีก 4 เมธอดที่ **ไม่มีใครเรียกเลย** และลบทิ้งไปแล้ว (2026-09-02):
 *
 * - `register()` — ทางสมัครคู่ขนานที่ **ทำงานไม่ได้ตั้งแต่แรก** ยิงจริงบน MySQL แล้ว
 *   ตายที่ INSERT แรกด้วย `Field 'name' doesn't have a default value` เพราะไม่เคยเซ็ต `name`
 *   (และไม่เซ็ต `personal_code`/`reference_code` ซึ่งก็ NOT NULL เหมือนกัน)
 *   ยังส่ง `referral_code`/`referrer_code`/`phone`/`avatar` ที่ไม่มีใน `$fillable`
 *   ⇒ ถ้าซ่อมคอลัมน์ที่ขาดแล้วปล่อยผ่าน มันจะสร้างบัญชีที่หลุดระบบผู้แนะนำทั้งหมด
 * - `createUserProfile()` — ใช้โดย `register()` เท่านั้น
 * - `generateTokenResponse()` — ซ้ำกับ `AuthController::respondWithToken()` แต่คีย์ตอบกลับ
 *   คนละแบบ (`accessToken`/`tokenType`/`expiresIn` แทน `access_token`/`token_type`/`expires_in`)
 *   ⇒ ถ้ามีใครหยิบไปใช้ frontend จะพังเงียบ ๆ
 * - `getAuthenticatedUser()` — ซ้ำกับ `AuthController::me()`
 *
 * ทางสมัครจริงอยู่ที่ `AuthController::register()` ที่เดียว — ห้ามสร้างทางคู่ขนานขึ้นมาอีก
 */
class AuthService
{
    /**
     * Assign default role to user.
     */
    public function assignDefaultRole(User $user): void
    {
        $studentRole = Role::where('name', 'STUDENT')->first();

        if ($studentRole) {
            $user->roles()->attach($studentRole->id);
        }
    }
}
