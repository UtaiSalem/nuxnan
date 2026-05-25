<?php

namespace Database\Seeders;

use App\Models\TypingWord;
use Illuminate\Database\Seeder;

class TypingWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $words = [
            // English - Easy
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'the'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'be'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'to'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'of'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'and'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'in'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'that'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'have'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'it'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'for'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'cat'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'dog'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'sun'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'red'],
            ['language' => 'en', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'blue'],

            // English - Normal
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'between'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'country'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'problem'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'program'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'system'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'computer'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'keyboard'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'monitor'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'network'],
            ['language' => 'en', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'software'],

            // English - Hard
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'achievement'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'environment'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'experience'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'information'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'opportunity'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'perspective'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'technology'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'understand'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'university'],
            ['language' => 'en', 'difficulty' => 'hard', 'category' => 'general', 'text' => 'everything'],

            // Thai - Easy
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'ไป'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'มา'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'กิน'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'นอน'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'รัก'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'ดี'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'ใจ'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'น้ำ'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'ข้าว'],
            ['language' => 'th', 'difficulty' => 'easy', 'category' => 'general', 'text' => 'พ่อ'],

            // Thai - Normal
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'นักเรียน'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'อาจารย์'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'โรงเรียน'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'มหาลัย'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'ท้องฟ้า'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'ทะเล'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'ภูเขา'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'ดอกไม้'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'คอมพิวเตอร์'],
            ['language' => 'th', 'difficulty' => 'normal', 'category' => 'general', 'text' => 'อินเทอร์เน็ต'],
        ];

        foreach ($words as $word) {
            TypingWord::create($word);
        }
    }
}
