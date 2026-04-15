<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncClassroomTablesData extends Command
{
    protected $signature = 'classrooms:sync-data
                            {--dry-run : Preview without making changes}';

    protected $description = 'Sync classroom-related tables with students/academies data';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $academyId = 1;
        $academicYearId = 1;

        $this->info('=== Classroom Tables Sync ===');
        $this->newLine();

        // 1. Update classrooms: set academic_year_id and academic_year
        $classroomsNoYear = DB::table('classrooms')
            ->where(function ($q) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', 0);
            })
            ->count();

        $this->info("1. classrooms without academic_year_id: {$classroomsNoYear}");
        if ($classroomsNoYear > 0 && !$dryRun) {
            DB::table('classrooms')
                ->where(function ($q) {
                    $q->whereNull('academic_year_id')->orWhere('academic_year_id', 0);
                })
                ->update([
                    'academic_year_id' => $academicYearId,
                    'academic_year' => '2568',
                ]);
            $this->info('   -> Updated!');
        }

        // 2. Update classroom_students: academy_id
        $csNoAcademy = DB::table('classroom_students')
            ->where(function ($q) {
                $q->whereNull('academy_id')->orWhere('academy_id', 0);
            })
            ->count();

        $this->info("2. classroom_students without academy_id: {$csNoAcademy}");
        if ($csNoAcademy > 0 && !$dryRun) {
            DB::table('classroom_students')
                ->where(function ($q) {
                    $q->whereNull('academy_id')->orWhere('academy_id', 0);
                })
                ->update(['academy_id' => $academyId]);
            $this->info('   -> Updated!');
        }

        // 3. Update classroom_students: academic_year_id
        $csNoYear = DB::table('classroom_students')
            ->whereNull('academic_year_id')
            ->count();

        $this->info("3. classroom_students without academic_year_id: {$csNoYear}");
        if ($csNoYear > 0 && !$dryRun) {
            DB::table('classroom_students')
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $academicYearId]);
            $this->info('   -> Updated!');
        }

        // 4. Update classroom_students: enrolled_at from students.enrollment_date
        $csNoEnrolled = DB::table('classroom_students')
            ->whereNull('enrolled_at')
            ->count();

        $this->info("4. classroom_students without enrolled_at: {$csNoEnrolled}");
        if ($csNoEnrolled > 0 && !$dryRun) {
            DB::statement("
                UPDATE classroom_students cs
                INNER JOIN students s ON s.id = cs.student_id
                SET cs.enrolled_at = COALESCE(s.enrollment_date, NOW())
                WHERE cs.enrolled_at IS NULL
            ");
            $this->info('   -> Updated from students.enrollment_date!');
        }

        // 5. Update classroom_students: student_number from students.student_id (code)
        $csNoNumber = DB::table('classroom_students')
            ->whereNull('student_number')
            ->count();

        $this->info("5. classroom_students without student_number: {$csNoNumber}");
        if ($csNoNumber > 0 && !$dryRun) {
            DB::statement("
                UPDATE classroom_students cs
                INNER JOIN students s ON s.id = cs.student_id
                SET cs.student_number = s.student_id
                WHERE cs.student_number IS NULL
                  AND s.student_id IS NOT NULL
            ");
            $this->info('   -> Updated from students.student_id!');
        }

        // Summary
        $this->newLine();
        $remaining = [];
        $remaining['classrooms.academic_year_id NULL'] = DB::table('classrooms')->whereNull('academic_year_id')->count();
        $remaining['classroom_students.academy_id NULL'] = DB::table('classroom_students')->whereNull('academy_id')->count();
        $remaining['classroom_students.academic_year_id NULL'] = DB::table('classroom_students')->whereNull('academic_year_id')->count();
        $remaining['classroom_students.enrolled_at NULL'] = DB::table('classroom_students')->whereNull('enrolled_at')->count();
        $remaining['classroom_students.student_number NULL'] = DB::table('classroom_students')->whereNull('student_number')->count();

        $this->table(
            ['Field', 'Remaining NULL'],
            collect($remaining)->map(fn($v, $k) => [$k, $v])->values()
        );

        if ($dryRun) {
            $this->warn('DRY RUN — No changes made.');
        } else {
            $this->info('All classroom tables synced!');
        }

        return 0;
    }
}
