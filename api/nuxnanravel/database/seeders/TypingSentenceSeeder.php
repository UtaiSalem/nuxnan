<?php

namespace Database\Seeders;

use App\Models\TypingSentence;
use Illuminate\Database\Seeder;

class TypingSentenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sentences = [
            // English - Easy
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'The quick brown fox jumps over the lazy dog.', 'word_count' => 9],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'She sells seashells by the seashore.', 'word_count' => 7],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'How much wood would a woodchuck chuck?', 'word_count' => 8],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Peter Piper picked a peck of pickled peppers.', 'word_count' => 9],

            // English - Normal
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'To be or not to be, that is the question.', 'word_count' => 10],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'The only thing we have to fear is fear itself.', 'word_count' => 11],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'That which does not kill us makes us stronger.', 'word_count' => 10],

            // Thai - Easy
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'กาสดาดา กาสดาดา กาสดาดา', 'word_count' => 6],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ในน้ำมีปลา ในนามีข้าว', 'word_count' => 6],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ประเทศไทยรวมเลือดเนื้อชาติเชื้อไทย', 'word_count' => 4],
        ];

        foreach ($sentences as $sentence) {
            TypingSentence::create($sentence);
        }
    }
}
