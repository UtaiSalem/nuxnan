<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'โพสต์แรก',
                'description' => 'สร้างโพสต์แรกของคุณ',
                'icon' => 'fluent:document-checkmark-24-filled',
                'xp_reward' => 50,
                'category' => 'activity',
            ],
            [
                'name' => 'สังคมออนไลน์',
                'description' => 'มีเพื่อน 10 คน',
                'icon' => 'fluent:people-24-filled',
                'xp_reward' => 100,
                'category' => 'social',
            ],
            [
                'name' => 'นักสะสมแต้ม',
                'description' => 'สะสม 100 แต้ม',
                'icon' => 'fluent:star-24-filled',
                'xp_reward' => 100,
                'category' => 'learning',
            ],
            [
                'name' => 'ผู้ชำนาญ',
                'description' => 'ถึง Level 5',
                'icon' => 'fluent:trophy-24-filled',
                'xp_reward' => 200,
                'category' => 'activity',
            ],
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate(
                ['name' => $badge['name']],
                $badge
            );
        }
    }
}
