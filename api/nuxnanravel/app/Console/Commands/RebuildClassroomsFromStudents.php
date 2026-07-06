<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildClassroomsFromStudents extends Command
{
    protected $signature = 'classrooms:rebuild-from-students
                            {--dry-run : Preview without making changes}';

    protected $description = 'Delete existing classrooms and create new ones from student grade/section data, then link everything';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $academyId = 1;

        // 1. Get unique grade/section combos from students
        $combos = DB::table('students')
            ->whereNotNull('class_level')
            ->where('class_level', '!=', '')
            ->selectRaw('class_level, class_section, count(*) as student_count')
            ->groupBy('class_level', 'class_section')
            ->orderByRaw('CAST(class_level AS UNSIGNED)')
            ->orderByRaw('CAST(class_section AS UNSIGNED)')
            ->get();

        $existingCount = Classroom::count();

        $this->info('=== Classroom Rebuild Plan ===');
        $this->info("Existing classrooms to delete: {$existingCount}");
        $this->info("New classrooms to create: {$combos->count()}");
        $this->newLine();

        $this->table(
            ['Grade', 'Section', 'Name', 'Students'],
            $combos->map(fn ($c) => [
                "ม.{$c->class_level}",
                $c->class_section,
                "ม.{$c->class_level}/{$c->class_section}",
                $c->student_count,
            ])
        );

        $totalStudents = $combos->sum('student_count');
        $noClassStudents = DB::table('students')
            ->where(function ($q) {
                $q->whereNull('class_level')->orWhere('class_level', '');
            })
            ->count();
        $this->info("Students to be assigned: {$totalStudents}");
        $this->info("Students without class info (won't be assigned): {$noClassStudents}");

        if ($dryRun) {
            $this->warn('DRY RUN — No changes made.');

            return 0;
        }

        if (! $this->confirm("Delete {$existingCount} old classrooms and create {$combos->count()} new ones?")) {
            $this->info('Cancelled.');

            return 0;
        }

        DB::beginTransaction();
        try {
            // 2. Delete old classrooms (and related pivot data)
            DB::table('classroom_students')->delete();
            DB::table('classroom_members')->delete();
            Classroom::query()->delete();
            $this->info("Deleted {$existingCount} old classrooms.");

            // 3. Create new classrooms
            $classroomMap = []; // "grade-section" => classroom_id
            $bar = $this->output->createProgressBar($combos->count());
            $bar->start();

            foreach ($combos as $combo) {
                $classroom = Classroom::create([
                    'academy_id' => $academyId,
                    'grade_level' => "ม.{$combo->class_level}",
                    'section' => $combo->class_section,
                    'name' => "ม.{$combo->class_level}/{$combo->class_section}",
                    'capacity' => 50,
                    'is_active' => true,
                    'status' => 'active',
                ]);

                $classroomMap["{$combo->class_level}-{$combo->class_section}"] = $classroom->id;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Created {$combos->count()} classrooms.");

            // 4. Update student_academic_info.classroom_id
            $academicUpdated = 0;
            foreach ($classroomMap as $key => $classroomId) {
                [$grade, $section] = explode('-', $key);
                $count = DB::table('student_academic_info as sa')
                    ->join('students as s', 's.id', '=', 'sa.student_id')
                    ->where('s.class_level', $grade)
                    ->where('s.class_section', $section)
                    ->update(['sa.classroom_id' => $classroomId]);
                $academicUpdated += $count;
            }
            $this->info("Updated student_academic_info.classroom_id: {$academicUpdated} records.");

            // 5. Populate classroom_students pivot
            $pivotInserted = 0;
            foreach ($classroomMap as $key => $classroomId) {
                [$grade, $section] = explode('-', $key);
                $students = DB::table('students')
                    ->where('class_level', $grade)
                    ->where('class_section', $section)
                    ->select('id')
                    ->get();

                $pivotData = $students->map(fn ($s) => [
                    'classroom_id' => $classroomId,
                    'student_id' => $s->id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                if (! empty($pivotData)) {
                    DB::table('classroom_students')->insert($pivotData);
                    $pivotInserted += count($pivotData);
                }
            }
            $this->info("Inserted classroom_students pivot: {$pivotInserted} records.");

            DB::commit();
            $this->newLine();
            $this->info('All done successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        // Cleanup temp files
        return 0;
    }
}
