<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * ผู้ใช้บนลีดเดอร์บอร์ด/การ์ดผู้บริจาคหน้าแรก — หน้าเว็บโชว์รหัสประจำตัวและแต้มอยู่แล้ว
 * ยังคงห้าม email, phone, wallet
 */
class PublicLeaderboardUserResource extends PublicUserResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'personal_code' => $this->personal_code,
            'points' => (int) ($this->pp ?? 0),
        ]);
    }
}
