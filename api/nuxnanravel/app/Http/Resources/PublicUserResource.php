<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ข้อมูลผู้ใช้แบบสาธารณะ — ใช้ทุกครั้งที่ส่งข้อมูลของ "คนอื่น" ให้ผู้ชม
 *
 * ห้ามเพิ่ม email, phone, phone_number, pp/points, wallet, locked_balance, personal_code
 * หรือคอลัมน์การเงิน/ความปลอดภัยใด ๆ ลงในไฟล์นี้
 */
class PublicUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->name,
            'username' => $this->username ?? $this->name,
            'avatar' => $this->profile_photo_url,
            'profile_photo_url' => $this->profile_photo_url,
            'reference_code' => $this->reference_code,
            'xp_level' => (int) ($this->xp_level ?? $this->level ?? 1),
            'level' => (int) ($this->level ?? $this->xp_level ?? 1),
        ];
    }
}
