<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const XP_AMOUNTS = ['login' => 10, 'lesson_complete' => 100, 'quiz_pass' => 500];

    public function up(): void
    {
        foreach (self::XP_AMOUNTS as $sourceType => $xpAmount) {
            DB::table('point_rules')->where('source_type', $sourceType)
                ->update(['base_amount' => 0, 'xp_amount' => $xpAmount]);
        }
    }

    public function down(): void
    {
        foreach (['login' => 1, 'lesson_complete' => 10, 'quiz_pass' => 50] as $sourceType => $baseAmount) {
            DB::table('point_rules')->where('source_type', $sourceType)
                ->update(['base_amount' => $baseAmount, 'xp_amount' => null]);
        }
    }
};
