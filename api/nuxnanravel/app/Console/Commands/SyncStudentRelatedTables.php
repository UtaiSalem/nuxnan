<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncStudentRelatedTables extends Command
{
    protected $signature = 'students:sync-related-tables
                            {--dry-run : Preview without making changes}';

    protected $description = 'Sync academy_id from students table to all student-related tables';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $tables = [
            'student_academic_info',
            'student_addresses',
            'student_contacts',
            'student_documents',
            'student_guardians',
            'student_health_info',
            'student_home_visits',
            'classroom_students',
            'gradebook_scores',
            'course_grades',
        ];

        $summary = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->line("{$table}: TABLE NOT FOUND — skipped");
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (!in_array('student_id', $columns) || !in_array('academy_id', $columns)) {
                $this->line("{$table}: missing student_id or academy_id column — skipped");
                continue;
            }

            $needsUpdate = DB::table("{$table} as t")
                ->join('students as s', 's.id', '=', 't.student_id')
                ->where(function ($q) {
                    $q->whereNull('t.academy_id')->orWhere('t.academy_id', 0);
                })
                ->whereNotNull('s.academy_id')
                ->count();

            if ($needsUpdate === 0) {
                $this->line("{$table}: already up to date");
                $summary[] = [$table, 0, 'OK'];
                continue;
            }

            $this->info("{$table}: {$needsUpdate} records to update");

            if (!$dryRun) {
                DB::statement("
                    UPDATE `{$table}` t
                    INNER JOIN students s ON s.id = t.student_id
                    SET t.academy_id = s.academy_id
                    WHERE (t.academy_id IS NULL OR t.academy_id = 0)
                      AND s.academy_id IS NOT NULL
                ");

                $this->info("  -> Updated!");
            }

            $summary[] = [$table, $needsUpdate, $dryRun ? 'DRY RUN' : 'UPDATED'];
        }

        // Handle student_cards separately (no student_id column)
        if (Schema::hasTable('student_cards') && in_array('academy_id', Schema::getColumnListing('student_cards'))) {
            $cardsNeedUpdate = DB::table('student_cards')
                ->where(function ($q) {
                    $q->whereNull('academy_id')->orWhere('academy_id', 0);
                })
                ->count();

            if ($cardsNeedUpdate > 0) {
                $this->info("student_cards: {$cardsNeedUpdate} records — setting academy_id = 1 (single academy)");
                if (!$dryRun) {
                    DB::table('student_cards')
                        ->where(function ($q) {
                            $q->whereNull('academy_id')->orWhere('academy_id', 0);
                        })
                        ->update(['academy_id' => 1]);
                    $this->info("  -> Updated!");
                }
                $summary[] = ['student_cards', $cardsNeedUpdate, $dryRun ? 'DRY RUN' : 'UPDATED'];
            } else {
                $summary[] = ['student_cards', 0, 'OK'];
            }
        }

        $this->newLine();
        $this->table(['Table', 'Records Updated', 'Status'], $summary);

        if ($dryRun) {
            $this->warn('DRY RUN — No changes were made.');
        } else {
            $this->info('All tables synced successfully.');
        }

        return 0;
    }
}
