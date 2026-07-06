<?php

namespace App\Console\Commands;

use App\Models\Academy;
use App\Models\Student;
use Illuminate\Console\Command;

class AssignStudentsToAcademy extends Command
{
    protected $signature = 'academy:assign-students
                            {academy_id : The academy ID to assign students to}
                            {--dry-run : Preview without making changes}';

    protected $description = 'Assign all students (without academy_id) to a specific academy';

    public function handle()
    {
        $academyId = $this->argument('academy_id');
        $dryRun = $this->option('dry-run');

        $academy = Academy::find($academyId);
        if (! $academy) {
            $this->error("Academy ID {$academyId} not found.");

            return 1;
        }

        $studentsWithout = Student::whereNull('academy_id')->count();
        $studentsAlready = Student::where('academy_id', $academyId)->count();
        $total = Student::count();

        $this->info("Academy: {$academy->name} (ID: {$academyId})");
        $this->info("Total students: {$total}");
        $this->info("Already assigned to this academy: {$studentsAlready}");
        $this->info("Without academy_id (will be updated): {$studentsWithout}");

        if ($studentsWithout === 0) {
            $this->info('All students already have academy_id assigned. Nothing to do.');

            return 0;
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No changes made.');

            return 0;
        }

        if (! $this->confirm("Assign {$studentsWithout} students to academy '{$academy->name}'?")) {
            $this->info('Cancelled.');

            return 0;
        }

        $updated = Student::whereNull('academy_id')->update(['academy_id' => $academyId]);

        $this->info("Updated {$updated} students with academy_id = {$academyId}");

        return 0;
    }
}
