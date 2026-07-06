<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Services\StudentCardAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StudentCardAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:card-audit {--academy=1} {--academic-year=} {--levels=} {--output=table} {--export=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit student cards and related records for inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle(StudentCardAuditService $auditService)
    {
        $academyId = $this->option('academy');
        $academicYearId = $this->option('academic-year');
        $levelsOpt = $this->option('levels');

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

        $levels = $levelsOpt ? explode(',', $levelsOpt) : [];

        $this->info("Auditing Academy: {$academy->name} | Year: {$year->name}");

        $report = $auditService->audit($academy, $year, $levels);

        $output = $this->option('output');
        if ($output === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return 0;
        }

        // Print to table format for console
        $this->info("\n--- Section A: M.6 ---");
        $this->line('Active M.6 Enrollments: '.$report['section_a_m6']['active_m6_enrollments']);
        $this->line('Active M.6 Cards: '.count($report['section_a_m6']['active_m6_cards']));
        $this->line('Unsynced Graduates (active cards but graduated enrollment): '.count($report['section_a_m6']['unsynced_graduates']));
        $this->line('Duplicate Active Cards: '.count($report['section_a_m6']['duplicate_active_cards']));
        $this->line('Missing Academic Info: '.count($report['section_a_m6']['missing_academic_info']));

        $this->info("\n--- Section B: New Intake (M.1, M.4) ---");
        $this->line('Pending Intakes (Active students without enrollment): '.count($report['section_b_new_intake']['pending_intakes']));
        $this->line('Enrolled Without Card: '.count($report['section_b_new_intake']['enrolled_without_card']));
        $this->line('Orphan Cards (Active card, student_id=null): '.count($report['section_b_new_intake']['orphan_cards']));
        $this->line('Missing Data: '.count($report['section_b_new_intake']['missing_data']));
        $this->line('Duplicate Student IDs: '.count($report['section_b_new_intake']['duplicate_student_ids']));
        $this->line('Duplicate Citizen IDs: '.count($report['section_b_new_intake']['duplicate_citizen_ids']));

        $this->info("\n--- Section C: Consistency ---");
        $this->line('Inconsistent Status: '.count($report['section_c_consistency']['inconsistent_status']));
        $this->line('Stale Card Snapshots: '.count($report['section_c_consistency']['stale_card_snapshots']));
        $this->line('Wrong Year Enrollments: '.count($report['section_c_consistency']['wrong_year_enrollments']));

        // Handle export
        $exportPath = $this->option('export');
        if ($exportPath) {
            // Simple json export for now as CSV requires mapping multiple sections
            Storage::disk('local')->put($exportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nExported full report to storage/app/{$exportPath}");
        }

        return 0;
    }
}
