<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORY = 'practice_expansion';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('typing_words')) {
            return;
        }

        $now = now();

        foreach ($this->words() as $word) {
            DB::table('typing_words')->updateOrInsert(
                [
                    'language' => $word['language'],
                    'difficulty' => $word['difficulty'],
                    'text' => $word['text'],
                ],
                [
                    'category' => self::CATEGORY,
                    'pronunciation' => $word['pronunciation'] ?? null,
                    'meaning' => $word['meaning'] ?? null,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('typing_words')) {
            return;
        }

        DB::table('typing_words')->where('category', self::CATEGORY)->delete();
    }

    private function words(): array
    {
        return [
            // Thai beginner
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'กด', 'meaning' => 'press'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'จำ', 'meaning' => 'remember'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ลอง', 'meaning' => 'try'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'เร็ว', 'meaning' => 'fast'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ช้า', 'meaning' => 'slow'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ซ้าย', 'meaning' => 'left'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ขวา', 'meaning' => 'right'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'บน', 'meaning' => 'up'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ล่าง', 'meaning' => 'down'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'มือ', 'meaning' => 'hand'],

            // Thai easy
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'แป้นพิมพ์', 'meaning' => 'keyboard'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ฝึกพิมพ์', 'meaning' => 'typing practice'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความเร็ว', 'meaning' => 'speed'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความแม่นยำ', 'meaning' => 'accuracy'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'บทเรียน', 'meaning' => 'lesson'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'คะแนน', 'meaning' => 'score'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'เป้าหมาย', 'meaning' => 'goal'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'เวลา', 'meaning' => 'time'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'สมาธิ', 'meaning' => 'focus'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ทักษะ', 'meaning' => 'skill'],

            // Thai normal
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การเรียนรู้ออนไลน์', 'meaning' => 'online learning'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ระบบการศึกษา', 'meaning' => 'education system'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'แบบฝึกหัด', 'meaning' => 'exercise'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ประสบการณ์', 'meaning' => 'experience'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความรับผิดชอบ', 'meaning' => 'responsibility'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความคิดสร้างสรรค์', 'meaning' => 'creativity'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การสื่อสาร', 'meaning' => 'communication'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การแก้ปัญหา', 'meaning' => 'problem solving'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การทำงานเป็นทีม', 'meaning' => 'teamwork'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'เทคโนโลยีดิจิทัล', 'meaning' => 'digital technology'],

            // Thai hard/expert
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ปัญญาประดิษฐ์', 'meaning' => 'artificial intelligence'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'วิทยาการข้อมูล', 'meaning' => 'data science'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ความปลอดภัยไซเบอร์', 'meaning' => 'cybersecurity'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'นวัตกรรมการเรียนรู้', 'meaning' => 'learning innovation'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การวิเคราะห์เชิงลึก', 'meaning' => 'deep analysis'],
            ['language' => 'th', 'difficulty' => 'expert', 'text' => 'สถาปัตยกรรมซอฟต์แวร์', 'meaning' => 'software architecture'],
            ['language' => 'th', 'difficulty' => 'expert', 'text' => 'การประมวลผลภาษาธรรมชาติ', 'meaning' => 'natural language processing'],
            ['language' => 'th', 'difficulty' => 'expert', 'text' => 'ระบบนิเวศการเรียนรู้ดิจิทัล', 'meaning' => 'digital learning ecosystem'],

            // English beginner/easy
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'tap', 'meaning' => 'press lightly'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'key', 'meaning' => 'keyboard key'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'type', 'meaning' => 'enter text'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'hand', 'meaning' => 'hand'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'desk', 'meaning' => 'desk'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'focus', 'meaning' => 'pay attention'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'typing', 'meaning' => 'entering text'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'practice', 'meaning' => 'training'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'accuracy', 'meaning' => 'correctness'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'speed', 'meaning' => 'quickness'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'lesson', 'meaning' => 'learning unit'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'progress', 'meaning' => 'improvement'],

            // English normal/hard/expert
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'dashboard', 'meaning' => 'summary screen'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'assignment', 'meaning' => 'learning task'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'feedback', 'meaning' => 'response or advice'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'collaboration', 'meaning' => 'working together'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'motivation', 'meaning' => 'reason to act'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'accessibility', 'meaning' => 'usable by everyone'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'synchronization', 'meaning' => 'keeping in sync'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'personalization', 'meaning' => 'tailoring to a person'],
            ['language' => 'en', 'difficulty' => 'expert', 'text' => 'interoperability', 'meaning' => 'systems working together'],
            ['language' => 'en', 'difficulty' => 'expert', 'text' => 'observability', 'meaning' => 'ability to inspect system state'],
        ];
    }
};
