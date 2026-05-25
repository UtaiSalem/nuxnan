<?php

namespace Database\Seeders;

use App\Models\TypingAchievement;
use Illuminate\Database\Seeder;

class TypingAchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'key'         => 'first_blood',
                'name'        => 'First Blood',
                'description' => 'เล่นเกมพิมพ์ครั้งแรก',
                'icon'        => '🎯',
                'condition'   => ['type' => 'first_game'],
                'xp_reward'   => 10,
                'is_active'   => true
            ],
            [
                'key'         => 'speed_20',
                'name'        => 'Getting Warmed',
                'description' => 'พิมพ์ได้ความเร็ว 20 WPM',
                'icon'        => '🔥',
                'condition'   => ['type' => 'wpm', 'value' => 20],
                'xp_reward'   => 20,
                'is_active'   => true
            ],
            [
                'key'         => 'speed_40',
                'name'        => 'Speed Typist',
                'description' => 'พิมพ์ได้ความเร็ว 40 WPM',
                'icon'        => '⚡',
                'condition'   => ['type' => 'wpm', 'value' => 40],
                'xp_reward'   => 50,
                'is_active'   => true
            ],
            [
                'key'         => 'speed_60',
                'name'        => 'Speed Demon',
                'description' => 'พิมพ์ได้ความเร็ว 60 WPM',
                'icon'        => '💨',
                'condition'   => ['type' => 'wpm', 'value' => 60],
                'xp_reward'   => 100,
                'is_active'   => true
            ],
            [
                'key'         => 'perfect_acc',
                'name'        => 'Perfectionist',
                'description' => 'พิมพ์ด้วยความแม่นยำ 100%',
                'icon'        => '💎',
                'condition'   => ['type' => 'accuracy', 'value' => 100],
                'xp_reward'   => 100,
                'is_active'   => true
            ],
            [
                'key'         => 'combo_10',
                'name'        => 'Combo King',
                'description' => 'ทำ Combo ได้ถึง 10 ครั้ง',
                'icon'        => '👑',
                'condition'   => ['type' => 'combo', 'value' => 10],
                'xp_reward'   => 30,
                'is_active'   => true
            ],
            [
                'key'         => 'combo_30',
                'name'        => 'Combo Legend',
                'description' => 'ทำ Combo ได้ถึง 30 ครั้ง',
                'icon'        => '🌟',
                'condition'   => ['type' => 'combo', 'value' => 30],
                'xp_reward'   => 80,
                'is_active'   => true
            ],
            [
                'key'         => 'veteran_10',
                'name'        => 'Dedicated Typist',
                'description' => 'เล่นครบ 10 ครั้ง',
                'icon'        => '🏅',
                'condition'   => ['type' => 'session_count', 'value' => 10],
                'xp_reward'   => 50,
                'is_active'   => true
            ],
        ];

        foreach ($achievements as $achievement) {
            TypingAchievement::updateOrCreate(
                ['key' => $achievement['key']],
                [
                    ...$achievement,
                    'condition' => json_encode($achievement['condition'])
                ]
            );
        }
    }
}
