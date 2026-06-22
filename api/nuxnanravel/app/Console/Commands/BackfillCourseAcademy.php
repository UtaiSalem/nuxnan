<?php

namespace App\Console\Commands;

use App\Models\Academy;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCourseAcademy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:backfill-academy
                            {--academy=1 : Target academy ID}
                            {--year=2569 : Academic year to set/verify}
                            {--ids=22,23,24,25 : Comma-separated course IDs to patch}
                            {--dry-run : Show what would change without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill academy_id and fix missing academic_year for courses copied from a previous year';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $academyId = (int) $this->option('academy');
        $year = (int) $this->option('year');
        $idsOption = $this->option('ids');
        $ids = array_filter(explode(',', $idsOption), fn ($id) => is_numeric($id));
        $ids = array_map('intval', $ids);
        $dryRun = (bool) $this->option('dry-run');

        $academy = Academy::find($academyId);
        if (! $academy) {
            $this->error("Academy {$academyId} not found.");

            return self::FAILURE;
        }

        if (empty($ids)) {
            $this->error('No valid course IDs provided via --ids.');

            return self::FAILURE;
        }

        $courses = Course::whereIn('id', $ids)->get();
        if ($courses->count() !== count($ids)) {
            $missing = array_diff($ids, $courses->pluck('id')->all());
            $this->warn('Some IDs not found: '.implode(',', $missing));
        }

        if ($courses->isEmpty()) {
            $this->error('No courses found matching the provided IDs.');

            return self::FAILURE;
        }

        $this->info('Previewing course patch targets:');
        $this->table(
            ['ID', 'Name', 'Academic Year (Before)', 'Academy ID (Before)'],
            $courses->map(fn ($c) => [
                $c->id,
                $c->name,
                $c->academic_year ?: '(empty)',
                $c->academy_id ?? 'NULL',
            ])->toArray()
        );

        // Warning if any target already has different values
        foreach ($courses as $c) {
            if (! empty($c->academic_year) && (int) $c->academic_year !== $year) {
                $this->warn("Course #{$c->id} already has academic_year={$c->academic_year} (expected {$year}) - it will be overwritten.");
            }
            if (! empty($c->academy_id) && (int) $c->academy_id !== $academyId) {
                $this->warn("Course #{$c->id} already has academy_id={$c->academy_id} (expected {$academyId}) - it will be overwritten.");
            }
        }

        if ($dryRun) {
            $this->info('Dry run: no changes written.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Patch {$courses->count()} course(s) to academy_id={$academyId}, academic_year={$year}?", true)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($courses, $academy, $academyId, $year, $ids) {
            Course::whereIn('id', $ids)->update([
                'academy_id' => $academyId,
                'academic_year' => $year,
            ]);

            $actualCount = Course::where('academy_id', $academyId)->count();
            $academy->update(['courses_offered' => $actualCount]);

            $this->info("Patched {$courses->count()} course(s). courses_offered resynced to {$actualCount}.");
        });

        return self::SUCCESS;
    }
}
