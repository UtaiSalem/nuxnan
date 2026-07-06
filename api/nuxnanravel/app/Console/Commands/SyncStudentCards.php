<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\User;
use App\Services\StudentCardSyncService;
use Illuminate\Console\Command;

class SyncStudentCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:sync-cards {--academy=} {--academic-year=} {--preview} {--commit} {--user=} {--force : Commit without an interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync student cards with actual enrollments';

    /**
     * Execute the console command.
     */
    public function handle(StudentCardSyncService $syncService)
    {
        $academyId = $this->option('academy');
        $academicYearId = $this->option('academic-year');
        $isPreview = $this->option('preview');
        $isCommit = $this->option('commit');
        $userId = $this->option('user');

        if (! $academyId) {
            $this->error('The --academy option is required.');

            return 1;
        }

        if (! $isPreview && ! $isCommit) {
            $this->error('You must specify either --preview or --commit');

            return 1;
        }

        $academy = Academy::find($academyId);
        if (! $academy) {
            $this->error("Academy not found: {$academyId}");

            return 1;
        }

        $year = $academicYearId ? AcademicYear::find($academicYearId) : AcademicYear::where('academy_id', $academy->id)->where('is_current', 1)->first();
        if (! $year) {
            $this->error('Academic year not found');

            return 1;
        }

        $user = User::find($userId);
        if (! $user && $isCommit) {
            $this->error("User not found: {$userId}");

            return 1;
        }

        $this->info("Syncing Cards for Academy: {$academy->name} | Year: {$year->name}");

        if ($isPreview) {
            $report = $syncService->previewSync($academy, $year);
            $this->info('Preview Results:');
            $this->line('Create: '.$report['counts']['create']);
            $this->line('Update: '.$report['counts']['update']);
            $this->line('Expire: '.$report['counts']['expire']);
            $this->line('Unchanged: '.$report['counts']['unchanged']);
            $this->line('Orphan: '.$report['counts']['orphan']);
        }

        if ($isCommit) {
            if (! $userId) {
                $this->error('The --user option is required when using --commit.');

                return 1;
            }

            if ($this->option('force') || $this->confirm('Do you wish to commit these changes?')) {
                $result = $syncService->commitSync($academy, $year, $user);
                $this->info('Commit Results:');
                $this->line('Created: '.$result['created']);
                $this->line('Updated: '.$result['updated']);
                $this->line('Expired: '.$result['expired']);
            }
        }

        return 0;
    }
}
