<?php

namespace App\Console\Commands;

use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Services\StudentEnrollmentService;
use Illuminate\Console\Command;

class EnrollmentRepairDirtyData extends Command
{
    public function __construct(private StudentEnrollmentService $enrollmentService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollment:repair-dirty-data {--dry-run : Only show what would be updated} {--academy= : Limit repair to a specific academy ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair dirty enrollment data: duplicate active classroom students, student class snapshots drift, duplicate current academic info, and identify manual review items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $academyId = $this->option('academy');
        $commit = ! $dryRun;

        $this->info('Starting enrollment:repair-dirty-data command. Mode: '.($commit ? 'COMMIT' : 'DRY-RUN'));
        if ($academyId) {
            $this->info("Filtering by Academy ID: {$academyId}");
        }

        // Keep track of statistics
        $stats = [
            'duplicate_active_fixed' => 0,
            'student_snapshots_resynced' => 0,
            'duplicate_current_demoted' => 0,
            'manual_review_rows' => 0,

            // Detailed lists for logging
            'duplicate_active_details' => [],
            'student_drift_details' => [],
            'duplicate_current_details' => [],
            'manual_review_details' => [],
        ];

        // Chunk students to process
        $studentQuery = Student::query();
        if ($academyId) {
            $studentQuery->where('academy_id', $academyId);
        }

        $studentQuery->chunkById(100, function ($students) use ($commit, &$stats) {
            foreach ($students as $student) {
                $studentName = $student->full_name_th ?: "{$student->first_name_th} {$student->last_name_th}";

                // 1. DUPLICATE ACTIVE CLASSROOM STUDENTS
                // Group active enrollments by academic year
                $activeEnrollments = ClassroomStudent::where('student_id', $student->id)
                    ->where('status', 'active')
                    ->get();

                // Check manual review: active enrollment with null academic_year_id
                foreach ($activeEnrollments as $row) {
                    if ($row->academic_year_id === null) {
                        $stats['manual_review_rows']++;
                        $stats['manual_review_details'][] = [
                            'student_id' => $student->id,
                            'student_name' => $studentName,
                            'type' => 'null_academic_year_id',
                            'description' => "Active ClassroomStudent #{$row->id} has null academic_year_id",
                        ];
                    }

                    // Check manual review: classroom deleted or not related to academy
                    $classroom = $row->classroom;
                    if ($classroom === null) {
                        $stats['manual_review_rows']++;
                        $stats['manual_review_details'][] = [
                            'student_id' => $student->id,
                            'student_name' => $studentName,
                            'type' => 'classroom_deleted',
                            'description' => "Active ClassroomStudent #{$row->id} has deleted classroom_id #{$row->classroom_id}",
                        ];
                    } else {
                        if ($classroom->academy_id !== $row->academy_id || $classroom->academy_id !== $student->academy_id) {
                            $stats['manual_review_rows']++;
                            $stats['manual_review_details'][] = [
                                'student_id' => $student->id,
                                'student_name' => $studentName,
                                'type' => 'classroom_academy_mismatch',
                                'description' => "Active ClassroomStudent #{$row->id} has classroom belonging to Academy #{$classroom->academy_id} instead of Student Academy #{$student->academy_id}",
                            ];
                        }
                    }
                }

                $activeEnrollmentsByYear = $activeEnrollments->groupBy('academic_year_id');

                foreach ($activeEnrollmentsByYear as $academicYearId => $rows) {
                    if ($rows->count() > 1) {
                        // Keep newest active (largest ID)
                        $sorted = $rows->sortByDesc('id');
                        $winner = $sorted->first();
                        $supersededRows = $sorted->slice(1);

                        foreach ($supersededRows as $row) {
                            $stats['duplicate_active_details'][] = [
                                'student_id' => $student->id,
                                'student_name' => $studentName,
                                'academic_year_id' => $academicYearId,
                                'winner_id' => $winner->id,
                                'superseded_id' => $row->id,
                            ];
                            $stats['duplicate_active_fixed']++;

                            if ($commit) {
                                $row->update(['status' => 'superseded']);
                            }
                        }
                    }
                }

                // 2. STUDENT CLASS SNAPSHOT DRIFT
                // Get the current active enrollment (after resolving duplicates, there should be at most one active overall)
                $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                    ->where('status', 'active')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($activeEnrollment) {
                    $classroom = $activeEnrollment->classroom;
                    if ($classroom) {
                        $expectedLevel = $this->enrollmentService->normalizeGradeLevel($classroom->grade_level);
                        $expectedSection = $classroom->section;

                        if ($student->class_level !== $expectedLevel || $student->class_section !== $expectedSection) {
                            $stats['student_drift_details'][] = [
                                'student_id' => $student->id,
                                'student_name' => $studentName,
                                'current_level' => $student->class_level,
                                'current_section' => $student->class_section,
                                'expected_level' => $expectedLevel,
                                'expected_section' => $expectedSection,
                            ];
                            $stats['student_snapshots_resynced']++;

                            if ($commit) {
                                $student->update([
                                    'class_level' => $expectedLevel,
                                    'class_section' => $expectedSection,
                                ]);
                            }
                        }
                    }
                } else {
                    // "ถ้า student ไม่มี active enrollment แต่ยังมี snapshot ค้าง ต้องไม่ล้างทันทีใน Phase 9"
                    // So we do nothing if there is no active enrollment (we do NOT clear class_level / class_section to null).
                }

                // 3. DUPLICATE CURRENT ACADEMIC INFO
                $currentInfos = StudentAcademicInfo::where('student_id', $student->id)
                    ->where('is_current', true)
                    ->get();

                if ($currentInfos->count() > 1) {
                    // Sort: academic_year desc, then updated_at desc, then id desc
                    $sortedInfos = $currentInfos->sort(function ($a, $b) {
                        // Compare academic_year desc as integers or strings
                        $yearA = (int) $a->academic_year;
                        $yearB = (int) $b->academic_year;
                        if ($yearA !== $yearB) {
                            return $yearB <=> $yearA;
                        }

                        // Compare updated_at desc
                        $updatedA = $a->updated_at ? $a->updated_at->timestamp : 0;
                        $updatedB = $b->updated_at ? $b->updated_at->timestamp : 0;
                        if ($updatedA !== $updatedB) {
                            return $updatedB <=> $updatedA;
                        }

                        // Compare id desc
                        return $b->id <=> $a->id;
                    });

                    $winnerInfo = $sortedInfos->first();
                    $supersededInfos = $sortedInfos->slice(1);

                    foreach ($supersededInfos as $info) {
                        $stats['duplicate_current_details'][] = [
                            'student_id' => $student->id,
                            'student_name' => $studentName,
                            'winner_id' => $winnerInfo->id,
                            'winner_year' => $winnerInfo->academic_year,
                            'superseded_id' => $info->id,
                            'superseded_year' => $info->academic_year,
                        ];
                        $stats['duplicate_current_demoted']++;

                        if ($commit) {
                            $info->update(['is_current' => false]);
                        }
                    }
                }

                // 4. MANUAL REVIEW: student_academic_info classroom/year mismatch with active enrollment
                if ($activeEnrollment && $activeEnrollment->classroom) {
                    $activeClassroom = $activeEnrollment->classroom;
                    $activeClassroomYear = $activeClassroom->academicYear?->name ?? $activeClassroom->academic_year;

                    $currentAcademicInfo = StudentAcademicInfo::where('student_id', $student->id)
                        ->where('is_current', true)
                        ->first();

                    if ($currentAcademicInfo) {
                        if ($currentAcademicInfo->classroom_id != $activeEnrollment->classroom_id ||
                            $currentAcademicInfo->academic_year != $activeClassroomYear) {

                            $stats['manual_review_rows']++;
                            $stats['manual_review_details'][] = [
                                'student_id' => $student->id,
                                'student_name' => $studentName,
                                'type' => 'academic_info_mismatch',
                                'description' => "StudentAcademicInfo #{$currentAcademicInfo->id} ({$currentAcademicInfo->classroom_id} / {$currentAcademicInfo->academic_year}) mismatches active ClassroomStudent ({$activeEnrollment->classroom_id} / {$activeClassroomYear})",
                            ];
                        }
                    }
                }
            }
        });

        // Emit human-readable report
        $this->info("\n==================================================");
        $this->info('REPAIR REPORT Summary');
        $this->info('==================================================');

        $this->info("\n1. Duplicate active classroom students:");
        $this->line("   - Fixed/Superseded: {$stats['duplicate_active_fixed']} records");
        foreach ($stats['duplicate_active_details'] as $detail) {
            $this->warn("     [Duplicate] Student #{$detail['student_id']} ({$detail['student_name']}) in AcademicYear #{$detail['academic_year_id']}: Row #{$detail['superseded_id']} superseded by Row #{$detail['winner_id']}");
        }

        $this->info("\n2. Student class snapshots drift:");
        $this->line("   - Re-synced: {$stats['student_snapshots_resynced']} records");
        foreach ($stats['student_drift_details'] as $detail) {
            $this->warn("     [Drift] Student #{$detail['student_id']} ({$detail['student_name']}): Current ({$detail['current_level']}/{$detail['current_section']}) -> Expected ({$detail['expected_level']}/{$detail['expected_section']})");
        }

        $this->info("\n3. Duplicate current academic info:");
        $this->line("   - Demoted/Unset: {$stats['duplicate_current_demoted']} records");
        foreach ($stats['duplicate_current_details'] as $detail) {
            $this->warn("     [Duplicate] Student #{$detail['student_id']} ({$detail['student_name']}): Info Row #{$detail['superseded_id']} ({$detail['superseded_year']}) unset in favor of Row #{$detail['winner_id']} ({$detail['winner_year']})");
        }

        $this->info("\n4. Manual review required cases:");
        $this->line("   - Total issues: {$stats['manual_review_rows']}");
        foreach ($stats['manual_review_details'] as $detail) {
            $this->error("     [Manual Review] Student #{$detail['student_id']} ({$detail['student_name']}): {$detail['description']}");
        }

        $this->info("\n==================================================");
        if ($dryRun) {
            $this->warn('DRY RUN completed. No changes were saved to the database.');
        } else {
            $this->info('COMMIT completed. Database successfully updated.');
        }
        $this->info('==================================================');

        // Emit machine-readable summary
        $summary = [
            'dry_run' => $dryRun,
            'commit' => $commit,
            'academy_id' => $academyId,
            'stats' => [
                'duplicate_active_fixed' => $stats['duplicate_active_fixed'],
                'student_snapshots_resynced' => $stats['student_snapshots_resynced'],
                'duplicate_current_demoted' => $stats['duplicate_current_demoted'],
                'manual_review_rows' => $stats['manual_review_rows'],
            ],
        ];
        $this->line("\nJSON_SUMMARY:".json_encode($summary));
    }
}
