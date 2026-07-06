<?php

namespace Database\Seeders;

use App\Models\LevelDefinition;
use App\Models\PointRule;
use App\Models\QuestDefinition;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Level Definitions
        for ($i = 1; $i <= 50; $i++) {
            $xpRequired = (int) (100 * pow($i, 1.5));
            LevelDefinition::updateOrCreate(
                ['level' => $i],
                [
                    'name' => "Level $i",
                    'name_th' => "เลเวล $i",
                    'xp_required' => $xpRequired,
                    'color' => $this->getLevelColor($i),
                ]
            );
        }

        // 2. Default Quest Definitions
        $quests = [
            [
                'quest_key' => 'daily_login',
                'title' => 'Daily Login',
                'title_th' => 'เข้าสู่ระบบประจำวัน',
                'description' => 'Log in to the platform today',
                'quest_type' => 'daily',
                'event_type' => 'login',
                'target_count' => 1,
                'points_reward' => 5,
                'xp_reward' => 50,
            ],
            [
                'quest_key' => 'lesson_explorer',
                'title' => 'Lesson Explorer',
                'title_th' => 'นักสำรวจบทเรียน',
                'description' => 'Complete 3 lessons in one day',
                'quest_type' => 'daily',
                'event_type' => 'lesson_complete',
                'target_count' => 3,
                'points_reward' => 15,
                'xp_reward' => 150,
            ],
            [
                'quest_key' => 'quiz_master',
                'title' => 'Quiz Master',
                'title_th' => 'จอมวางแผนสอบ',
                'description' => 'Pass 1 quiz',
                'quest_type' => 'daily',
                'event_type' => 'quiz_pass',
                'target_count' => 1,
                'points_reward' => 20,
                'xp_reward' => 200,
            ],
        ];

        foreach ($quests as $quest) {
            QuestDefinition::updateOrCreate(['quest_key' => $quest['quest_key']], $quest);
        }

        // 3. Ensure Point Rules exist for the events
        $rules = [
            [
                'rule_key' => 'login_points',
                'rule_name' => 'เข้าสู่ระบบ',
                'action_type' => 'earn',
                'source_type' => 'login',
                'base_amount' => 1,
            ],
            [
                'rule_key' => 'lesson_complete_points',
                'rule_name' => 'เรียนจบปทเรียน',
                'action_type' => 'earn',
                'source_type' => 'lesson_complete',
                'base_amount' => 10,
            ],
            [
                'rule_key' => 'quiz_pass_points',
                'rule_name' => 'สอบผ่าน',
                'action_type' => 'earn',
                'source_type' => 'quiz_pass',
                'base_amount' => 50,
            ],
        ];

        foreach ($rules as $rule) {
            PointRule::updateOrCreate(['rule_key' => $rule['rule_key']], $rule);
        }
    }

    protected function getLevelColor(int $level): string
    {
        if ($level < 10) {
            return '#94a3b8';
        } // Slate
        if ($level < 20) {
            return '#4ade80';
        } // Green
        if ($level < 30) {
            return '#60a5fa';
        } // Blue
        if ($level < 40) {
            return '#a78bfa';
        } // Violet

        return '#f472b6'; // Pink
    }
}
