<?php

namespace App\Support;

use App\Models\User;

/**
 * หาเจ้าของโปรไฟล์จากตัวระบุใน URL ที่เดียว (ใช้ทุก endpoint /api/users/{identifier}/...)
 *
 * - ตัวเลขล้วน = id ของผู้ใช้ ถ้าไม่เจอค่อยลอง personal_code (รหัสผู้แนะนำ 8 หลัก)
 * - นอกนั้น = reference_code ถ้าไม่เจอค่อยลอง username
 * - ไม่จับคู่ด้วย name (ชื่อแสดงซ้ำกันได้ จะได้ผู้ใช้ผิดคน)
 */
class ProfileUserResolver
{
    public static function resolve(?string $identifier): ?User
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        if (ctype_digit($identifier)) {
            return User::find((int) $identifier)
                ?? User::where('personal_code', $identifier)->first();
        }

        return User::where('reference_code', $identifier)->first()
            ?? User::where('username', $identifier)->first();
    }
}
