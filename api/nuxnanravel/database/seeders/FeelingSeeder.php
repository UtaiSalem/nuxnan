<?php

namespace Database\Seeders;

use App\Models\Feeling;
use Illuminate\Database\Seeder;

class FeelingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feelings = [
            // Positive feelings
            ['name' => 'happy', 'name_th' => 'มีความสุข', 'icon' => '😊', 'sort_order' => 1],
            ['name' => 'blessed', 'name_th' => 'เป็นสิริมงคล', 'icon' => '🙏', 'sort_order' => 2],
            ['name' => 'loved', 'name_th' => 'มีความรัก', 'icon' => '🥰', 'sort_order' => 3],
            ['name' => 'excited', 'name_th' => 'ตื่นเต้น', 'icon' => '🤩', 'sort_order' => 4],
            ['name' => 'grateful', 'name_th' => 'สำนึกในบุญคุณ', 'icon' => '💖', 'sort_order' => 5],
            ['name' => 'thankful', 'name_th' => 'ขอบคุณ', 'icon' => '🙌', 'sort_order' => 6],
            ['name' => 'wonderful', 'name_th' => 'ยอดเยี่ยม', 'icon' => '✨', 'sort_order' => 7],
            ['name' => 'amazing', 'name_th' => 'น่าทึ่ง', 'icon' => '🤯', 'sort_order' => 8],
            ['name' => 'joyful', 'name_th' => 'ร่าเริง', 'icon' => '😁', 'sort_order' => 9],
            ['name' => 'proud', 'name_th' => 'ภูมิใจ', 'icon' => '😤', 'sort_order' => 10],
            ['name' => 'hopeful', 'name_th' => 'มีความหวัง', 'icon' => '🌟', 'sort_order' => 11],
            ['name' => 'motivated', 'name_th' => 'มีแรงบันดาลใจ', 'icon' => '💪', 'sort_order' => 12],
            ['name' => 'relaxed', 'name_th' => 'ผ่อนคลาย', 'icon' => '😌', 'sort_order' => 13],
            ['name' => 'peaceful', 'name_th' => 'สงบ', 'icon' => '☮️', 'sort_order' => 14],
            ['name' => 'content', 'name_th' => 'พอใจ', 'icon' => '😊', 'sort_order' => 15],

            // Neutral feelings
            ['name' => 'thoughtful', 'name_th' => 'ครุ่นคิด', 'icon' => '🤔', 'sort_order' => 20],
            ['name' => 'curious', 'name_th' => 'อยากรู้', 'icon' => '🧐', 'sort_order' => 21],
            ['name' => 'nostalgic', 'name_th' => 'คิดถึงอดีต', 'icon' => '😢', 'sort_order' => 22],
            ['name' => 'surprised', 'name_th' => 'ประหลาดใจ', 'icon' => '😮', 'sort_order' => 23],
            ['name' => 'crazy', 'name_th' => 'บ้าบอ', 'icon' => '🤪', 'sort_order' => 24],
            ['name' => 'silly', 'name_th' => 'งี่เง่า', 'icon' => '😜', 'sort_order' => 25],
            ['name' => 'hungry', 'name_th' => 'หิว', 'icon' => '🤤', 'sort_order' => 26],
            ['name' => 'sleepy', 'name_th' => 'ง่วง', 'icon' => '😴', 'sort_order' => 27],
            ['name' => 'tired', 'name_th' => 'เหนื่อย', 'icon' => '😩', 'sort_order' => 28],
            ['name' => 'lazy', 'name_th' => 'ขี้เกียจ', 'icon' => '🦥', 'sort_order' => 29],

            // Negative feelings
            ['name' => 'sad', 'name_th' => 'เศร้า', 'icon' => '😢', 'sort_order' => 40],
            ['name' => 'angry', 'name_th' => 'โกรธ', 'icon' => '😠', 'sort_order' => 41],
            ['name' => 'frustrated', 'name_th' => 'หงุดหงิด', 'icon' => '😤', 'sort_order' => 42],
            ['name' => 'annoyed', 'name_th' => 'รำคาญ', 'icon' => '😒', 'sort_order' => 43],
            ['name' => 'disappointed', 'name_th' => 'ผิดหวัง', 'icon' => '😞', 'sort_order' => 44],
            ['name' => 'worried', 'name_th' => 'กังวล', 'icon' => '😟', 'sort_order' => 45],
            ['name' => 'anxious', 'name_th' => 'วิตกกังวล', 'icon' => '😰', 'sort_order' => 46],
            ['name' => 'stressed', 'name_th' => 'เครียด', 'icon' => '😫', 'sort_order' => 47],
            ['name' => 'lonely', 'name_th' => 'เหงา', 'icon' => '😔', 'sort_order' => 48],
            ['name' => 'confused', 'name_th' => 'สับสน', 'icon' => '😕', 'sort_order' => 49],
            ['name' => 'sick', 'name_th' => 'ป่วย', 'icon' => '🤒', 'sort_order' => 50],
            ['name' => 'heartbroken', 'name_th' => 'อกหัก', 'icon' => '💔', 'sort_order' => 51],
        ];

        foreach ($feelings as $feeling) {
            Feeling::updateOrCreate(
                ['name' => $feeling['name']],
                array_merge($feeling, [
                    'category' => 'feeling',
                    'is_active' => true,
                ])
            );
        }
    }
}
