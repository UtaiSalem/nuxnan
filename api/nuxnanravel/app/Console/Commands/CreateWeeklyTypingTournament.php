<?php

namespace App\Console\Commands;

use App\Models\TypingTournament;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateWeeklyTypingTournament extends Command
{
    protected $signature = 'typing:create-weekly-tournament';
    protected $description = "Create next week's typing tournament";

    public function handle(): void
    {
        $nextMonday = now()->next('Monday')->startOfDay();
        $nextSunday = $nextMonday->copy()->endOfWeek();
        $weekNum = $nextMonday->weekOfYear;
        $year = $nextMonday->year;

        $slug = "weekly-{$year}-w{$weekNum}";

        if (TypingTournament::where('slug', $slug)->exists()) {
            $this->info("Weekly tournament for W{$weekNum} already exists.");
            return;
        }

        // Cycle through configs each week
        $configs = [
            ['language' => 'en', 'difficulty' => 'normal', 'game_mode' => 'time_attack', 'time_limit' => 60],
            ['language' => 'th', 'difficulty' => 'easy', 'game_mode' => 'word_typing', 'time_limit' => 60],
            ['language' => 'en', 'difficulty' => 'hard', 'game_mode' => 'time_attack', 'time_limit' => 60],
            ['language' => 'any', 'difficulty' => 'normal', 'game_mode' => 'sentence_typing', 'time_limit' => 90],
        ];
        $config = $configs[$weekNum % count($configs)];

        $tournament = TypingTournament::create([
            'name' => "Weekly Speed Challenge #{$weekNum}",
            'slug' => $slug,
            'description' => "พิมพ์ให้ได้ WPM สูงสุดภายในสัปดาห์ที่ {$weekNum} ปี {$year} — เล่นได้ไม่จำกัดครั้ง!",
            'type' => 'weekly',
            'language' => $config['language'],
            'difficulty' => $config['difficulty'],
            'game_mode' => $config['game_mode'],
            'time_limit' => $config['time_limit'],
            'starts_at' => $nextMonday,
            'ends_at' => $nextSunday,
            'status' => 'upcoming',
            'prize_1st_xp' => 500,
            'prize_1st_pp' => 100,
            'prize_2nd_xp' => 300,
            'prize_2nd_pp' => 50,
            'prize_3rd_xp' => 150,
            'prize_3rd_pp' => 25,
            'prize_all_xp' => 20,
            'banner_color' => collect(['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981'])->random(),
        ]);

        $this->info("✅ Weekly tournament W{$weekNum} created: {$nextMonday->toDateString()} – {$nextSunday->toDateString()}");
    }
}
