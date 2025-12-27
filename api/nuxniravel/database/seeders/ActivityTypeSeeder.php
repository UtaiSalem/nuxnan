<?php

namespace Database\Seeders;

use App\Models\ActivityTypeModel;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activityTypes = [
            [
                'name' => 'watching',
                'name_th' => 'กำลังดู',
                'icon' => '📺',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 1,
            ],
            [
                'name' => 'listening',
                'name_th' => 'กำลังฟัง',
                'icon' => '🎧',
                'preposition' => 'to',
                'preposition_th' => '',
                'sort_order' => 2,
            ],
            [
                'name' => 'reading',
                'name_th' => 'กำลังอ่าน',
                'icon' => '📖',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 3,
            ],
            [
                'name' => 'playing',
                'name_th' => 'กำลังเล่น',
                'icon' => '🎮',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 4,
            ],
            [
                'name' => 'eating',
                'name_th' => 'กำลังกิน',
                'icon' => '🍽️',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 5,
            ],
            [
                'name' => 'drinking',
                'name_th' => 'กำลังดื่ม',
                'icon' => '🍹',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 6,
            ],
            [
                'name' => 'traveling',
                'name_th' => 'กำลังเดินทาง',
                'icon' => '✈️',
                'preposition' => 'to',
                'preposition_th' => 'ไป',
                'sort_order' => 7,
            ],
            [
                'name' => 'celebrating',
                'name_th' => 'กำลังฉลอง',
                'icon' => '🎉',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 8,
            ],
            [
                'name' => 'attending',
                'name_th' => 'กำลังเข้าร่วม',
                'icon' => '🎫',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 9,
            ],
            [
                'name' => 'supporting',
                'name_th' => 'กำลังสนับสนุน',
                'icon' => '📣',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 10,
            ],
            [
                'name' => 'looking_for',
                'name_th' => 'กำลังมองหา',
                'icon' => '🔍',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 11,
            ],
            [
                'name' => 'thinking_about',
                'name_th' => 'กำลังคิดเกี่ยวกับ',
                'icon' => '💭',
                'preposition' => '',
                'preposition_th' => '',
                'sort_order' => 12,
            ],
        ];

        foreach ($activityTypes as $type) {
            ActivityTypeModel::updateOrCreate(
                ['name' => $type['name']],
                array_merge($type, [
                    'is_active' => true,
                ])
            );
        }
    }
}
