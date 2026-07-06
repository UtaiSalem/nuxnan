<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignUserIdsToStudents extends Command
{
    protected $signature = 'students:assign-user-ids
                            {--dry-run : Preview without making changes}';

    protected $description = 'Assign user_id to students from academy_members records';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Count students without user_id
        $withoutUserId = DB::table('students')
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', 0);
            })
            ->count();

        // Count how many can be filled from academy_members
        $canFill = DB::table('students as s')
            ->join('academy_members as am', 'am.student_id', '=', 's.id')
            ->where(function ($q) {
                $q->whereNull('s.user_id')->orWhere('s.user_id', 0);
            })
            ->whereNotNull('am.user_id')
            ->where('am.user_id', '>', 0)
            ->count();

        $this->info("Students without user_id: {$withoutUserId}");
        $this->info("Can fill from academy_members: {$canFill}");
        $this->info('Cannot fill (no matching user): '.($withoutUserId - $canFill));

        if ($canFill === 0) {
            $this->info('Nothing to update.');

            return 0;
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No changes made.');

            return 0;
        }

        if (! $this->confirm("Update {$canFill} students with user_id from academy_members?")) {
            $this->info('Cancelled.');

            return 0;
        }

        $updated = DB::statement('
            UPDATE students s
            INNER JOIN academy_members am ON am.student_id = s.id
            SET s.user_id = am.user_id
            WHERE (s.user_id IS NULL OR s.user_id = 0)
              AND am.user_id IS NOT NULL
              AND am.user_id > 0
        ');

        // Count how many were actually updated
        $remaining = DB::table('students')
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', 0);
            })
            ->count();

        $actualUpdated = $withoutUserId - $remaining;
        $this->info("Updated {$actualUpdated} students with user_id.");

        if ($remaining > 0) {
            $this->warn("{$remaining} students still without user_id (no matching user found).");

            $orphans = DB::table('students')
                ->where(function ($q) {
                    $q->whereNull('user_id')->orWhere('user_id', 0);
                })
                ->select('id', 'student_id', 'first_name_th', 'last_name_th')
                ->get();

            $this->table(
                ['ID', 'Student Code', 'First Name', 'Last Name'],
                $orphans->map(fn ($s) => [$s->id, $s->student_id, $s->first_name_th, $s->last_name_th])
            );
        }

        return 0;
    }
}
