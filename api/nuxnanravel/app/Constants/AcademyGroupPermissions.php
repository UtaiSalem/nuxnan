<?php

namespace App\Constants;

class AcademyGroupPermissions
{
    public const PERMISSIONS = [
        'can_post' => ['label' => 'โพสต์ในนามส่วนงาน',  'default' => false],
        'can_invite_member' => ['label' => 'เชิญสมาชิกใหม่',       'default' => true],
        'can_remove_member' => ['label' => 'นำสมาชิกออก',          'default' => false],
        'can_pin_post' => ['label' => 'ปักหมุดโพสต์',          'default' => false],
        'can_create_event' => ['label' => 'สร้างกิจกรรม',          'default' => false],
        'can_send_announcement' => ['label' => 'ออกประกาศ',            'default' => false],
    ];

    public static function all(): array
    {
        return collect(self::PERMISSIONS)
            ->map(fn ($meta, $key) => array_merge(['key' => $key], $meta))
            ->values()
            ->all();
    }

    public static function keys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function defaultFor(string $key): bool
    {
        return self::PERMISSIONS[$key]['default'] ?? false;
    }
}
