<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $totalNullBefore = DB::table('classrooms')->whereNull('academic_year_id')->count();
        Log::info("Starting backfill for classrooms. academic_year_id is NULL for {$totalNullBefore} records.");

        $updatedCount = 0;

        DB::table('classrooms')
            ->whereNull('academic_year_id')
            ->orderBy('id')
            ->chunk(100, function ($rows) use (&$updatedCount) {
                foreach ($rows as $row) {
                    $academicYearName = $row->academic_year;

                    if (empty($academicYearName)) {
                        Log::warning("Classroom ID {$row->id} has NULL/empty academic_year string. Skipping backfill.");

                        continue;
                    }

                    // Look for existing academic year
                    $academicYear = DB::table('academic_years')
                        ->where('academy_id', $row->academy_id)
                        ->where('name', $academicYearName)
                        ->first();

                    if (! $academicYear) {
                        // Find-or-create fallback: generate default start/end dates
                        $yearInt = intval($academicYearName);
                        if ($yearInt > 0) {
                            $adYear = $yearInt - 543;
                            $startDate = "{$adYear}-05-16";
                            $endDate = ($adYear + 1).'-03-31';
                        } else {
                            $startDate = date('Y-05-16');
                            $endDate = date('Y-03-31', strtotime('+1 year'));
                        }

                        $academicYearId = DB::table('academic_years')->insertGetId([
                            'academy_id' => $row->academy_id,
                            'name' => $academicYearName,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'is_current' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info("Created missing academic year '{$academicYearName}' for academy ID {$row->academy_id}. Generated ID: {$academicYearId}");
                    } else {
                        $academicYearId = $academicYear->id;
                    }

                    DB::table('classrooms')
                        ->where('id', $row->id)
                        ->update([
                            'academic_year_id' => $academicYearId,
                        ]);

                    $updatedCount++;
                }
            });

        Log::info("Completed backfill for classrooms. Updated {$updatedCount} records.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Rollback of data-only backfill migration is a no-op.');
    }
};
