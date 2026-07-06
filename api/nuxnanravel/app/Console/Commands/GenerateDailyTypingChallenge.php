<?php

namespace App\Console\Commands;

use App\Models\TypingDailyChallenge;
use App\Models\TypingWord;
use Illuminate\Console\Command;

class GenerateDailyTypingChallenge extends Command
{
    protected $signature = 'typing:generate-daily {--date=}';

    protected $description = 'Generate daily typing challenge';

    public function handle(): void
    {
        $date = $this->option('date') ? now()->parse($this->option('date')) : now()->addDay();

        if (TypingDailyChallenge::whereDate('challenge_date', $date)->exists()) {
            $this->info("Challenge for {$date->toDateString()} already exists.");

            return;
        }

        $difficulties = ['easy', 'normal', 'hard'];
        $difficulty = $difficulties[array_rand($difficulties)];
        $langs = ['en', 'th'];
        $lang = $langs[array_rand($langs)];
        $modes = ['word_typing', 'time_attack', 'sentence_typing'];
        $mode = $modes[array_rand($modes)];

        $words = TypingWord::where('language', $lang)
            ->where('difficulty', $difficulty)
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(20)
            ->pluck('id');

        TypingDailyChallenge::create([
            'challenge_date' => $date->toDateString(),
            'game_mode' => $mode,
            'language' => $lang,
            'difficulty' => $difficulty,
            'content_ids' => $words,
            'target_wpm' => match ($difficulty) {
                'easy' => 25, 'normal' => 35, 'hard' => 50, default => 30
            },
            'target_acc' => 85.0,
            'xp_reward' => match ($difficulty) {
                'easy' => 30, 'normal' => 60, 'hard' => 100, default => 50
            },
            'pp_reward' => match ($difficulty) {
                'easy' => 5,  'normal' => 10, 'hard' => 20,  default => 10
            },
        ]);

        $this->info("✅ Daily challenge created for {$date->toDateString()}");
    }
}
