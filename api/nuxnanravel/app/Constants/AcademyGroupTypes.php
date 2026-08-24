<?php

namespace App\Constants;

class AcademyGroupTypes
{
    /**
     * Source of truth for academy_group.type metadata.
     * Mirror this when changing ui/constants/academyGroupTypes.ts.
     */
    public const TYPES = [
        'office' => [
            'label' => 'สำนัก',
            'label_en' => 'Office',
            'icon' => 'heroicons:building-office',
            'color' => 'purple',
            'order' => 1,
        ],
        'department' => [
            'label' => 'ฝ่าย',
            'label_en' => 'Department',
            'icon' => 'heroicons:briefcase',
            'color' => 'cyan',
            'order' => 2,
        ],
        'section' => [
            'label' => 'งาน',
            'label_en' => 'Section',
            'icon' => 'heroicons:clipboard-document-list',
            'color' => 'green',
            'order' => 3,
        ],
        'academic_group' => [
            'label' => 'กลุ่มสาระ',
            'label_en' => 'Academic Group',
            'icon' => 'heroicons:book-open',
            'color' => 'orange',
            'order' => 4,
        ],
        'classroom' => [
            'label' => 'ห้องเรียน',
            'label_en' => 'Classroom',
            'icon' => 'heroicons:academic-cap',
            'color' => 'cyan',
            'order' => 5,
        ],
        'club' => [
            'label' => 'ชมรม',
            'label_en' => 'Club',
            'icon' => 'heroicons:trophy',
            'color' => 'pink',
            'order' => 6,
        ],
        'committee' => [
            'label' => 'คณะกรรมการ',
            'label_en' => 'Committee',
            'icon' => 'heroicons:user-group',
            'color' => 'amber',
            'order' => 7,
        ],
        'dormitory' => [
            'label' => 'หอพัก',
            'label_en' => 'Dormitory',
            'icon' => 'heroicons:home-modern',
            'color' => 'teal',
            'order' => 8,
        ],
        'house' => [
            'label' => 'คณะสี',
            'label_en' => 'House',
            'icon' => 'heroicons:flag',
            'color' => 'purple',
            'order' => 9,
        ],
        'student_council' => [
            'label' => 'สภานักเรียน', 'label_en' => 'Student Council',
            'icon' => 'heroicons:megaphone', 'color' => 'pink', 'order' => 10,
        ],
    ];

    public static function all(): array
    {
        return collect(self::TYPES)
            ->map(fn ($meta, $key) => array_merge(['key' => $key], $meta))
            ->sortBy('order')
            ->values()
            ->all();
    }

    public static function get(?string $key): ?array
    {
        return $key ? (self::TYPES[$key] ?? null) : null;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::TYPES);
    }

    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }
}
