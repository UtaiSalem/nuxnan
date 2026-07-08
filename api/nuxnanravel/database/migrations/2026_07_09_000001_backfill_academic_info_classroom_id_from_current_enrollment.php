<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('student_academic_info')) {
            return;
        }

        // Count records before backfill
        $totalNullBefore = DB::table('student_academic_info')->whereNull('classroom_id')->count();
        Log::info("Starting backfill for student_academic_info. classroom_id is NULL for {$totalNullBefore} records.");

        $updatedCount = 0;

        DB::table('student_academic_info')
            ->whereNull('classroom_id')
            ->orderBy('id')
            ->chunk(100, function ($rows) use (&$updatedCount) {
                foreach ($rows as $row) {
                    $enrollmentQuery = DB::table('classroom_students as cs')
                        ->join('classrooms as c', 'cs.classroom_id', '=', 'c.id')
                        ->join('academic_years as ay', 'c.academic_year_id', '=', 'ay.id')
                        ->where('cs.student_id', $row->student_id)
                        ->select('cs.classroom_id', 'c.name as classroom_name');

                    $matched = null;

                    // 1. Try matching by academic year name
                    if (! empty($row->academic_year)) {
                        $matched = (clone $enrollmentQuery)
                            ->where('ay.name', $row->academic_year)
                            ->orderBy('cs.enrolled_at', 'desc')
                            ->orderBy('cs.id', 'desc')
                            ->first();
                    }

                    // 2. Fallback: match by current year or latest enrollment
                    if (! $matched) {
                        $matched = $enrollmentQuery
                            ->orderBy('ay.is_current', 'desc')
                            ->orderBy('cs.enrolled_at', 'desc')
                            ->orderBy('cs.id', 'desc')
                            ->first();
                    }

                    if ($matched) {
                        DB::table('student_academic_info')
                            ->where('id', $row->id)
                            ->update([
                                'classroom_id' => $matched->classroom_id,
                                'classroom_full' => $row->classroom_full ?: $matched->classroom_name,
                            ]);
                        $updatedCount++;
                    }
                }
            });

        Log::info("Completed backfill for student_academic_info. Updated {$updatedCount} records.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-reversible data backfill
        Log::info('Rollback of data-only backfill migration is a no-op.');
    }
};
