<?php

namespace App\Console\Commands;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAcademyMemberAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academy:fix-member-assignment 
                            {--academy= : Specific academy ID to process}
                            {--dry-run : Run without making changes}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Academy Member records by matching Students with Users based on email containing student_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $academyId = $this->option('academy');
        $dryRun = $this->option('dry-run');
        $detailed = $this->option('detailed');

        $this->info('=== Academy Member Assignment Fix ===');
        $this->info('');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->info('');
        }

        // Get all students
        $studentsQuery = Student::query();
        
        if ($academyId) {
            $studentsQuery->where('academy_id', $academyId);
            $this->info("Processing students for Academy ID: {$academyId}");
        } else {
            $this->info('Processing ALL students');
        }

        $students = $studentsQuery->get();
        $this->info("Found {$students->count()} students to process");
        $this->info('');

        $matched = 0;
        $notMatched = 0;
        $alreadyAssigned = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        $results = [];

        foreach ($students as $student) {
            $bar->advance();

            $studentCode = $student->student_id;
            
            if (empty($studentCode)) {
                if ($detailed) {
                    $this->newLine();
                    $this->warn("Student ID {$student->id} has no student_id code");
                }
                $notMatched++;
                continue;
            }

            // First priority: Use user_id from Student table if already assigned
            $user = null;
            
            if ($student->user_id) {
                $user = User::find($student->user_id);
            }
            
            // Fallback: Search for user with email containing the student code
            if (!$user) {
                // Common patterns: 
                // - studentcode@domain.com
                // - prefix_studentcode@domain.com
                // - studentcode_suffix@domain.com
                $user = User::where('email', 'LIKE', "{$studentCode}%")
                    ->orWhere('email', 'LIKE', "%{$studentCode}@%")
                    ->orWhere('email', 'LIKE', "%_{$studentCode}@%")
                    ->orWhere('email', 'LIKE', "%{$studentCode}_%")
                    ->first();
            }

            if (!$user) {
                if ($detailed) {
                    $this->newLine();
                    $this->line("No user found for student code: {$studentCode}");
                }
                $notMatched++;
                $results[] = [
                    'student_id' => $student->id,
                    'student_code' => $studentCode,
                    'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                    'status' => 'NO_USER_FOUND',
                    'user_email' => null,
                ];
                continue;
            }

            $matched++;

            // Determine the academy_id
            $targetAcademyId = $student->academy_id ?? $academyId;
            
            if (!$targetAcademyId) {
                if ($detailed) {
                    $this->newLine();
                    $this->warn("Student {$studentCode} has no academy_id");
                }
                $results[] = [
                    'student_id' => $student->id,
                    'student_code' => $studentCode,
                    'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                    'status' => 'NO_ACADEMY_ID',
                    'user_email' => $user->email,
                ];
                continue;
            }

            // Check if AcademyMember already exists with correct assignment
            $existingMember = AcademyMember::where('academy_id', $targetAcademyId)
                ->where(function ($query) use ($student, $user) {
                    $query->where('student_id', $student->id)
                        ->orWhere('user_id', $user->id);
                })
                ->first();

            if ($existingMember) {
                // Check if it needs update
                $needsUpdate = false;
                
                if ($existingMember->student_id != $student->id) {
                    $needsUpdate = true;
                }
                if ($existingMember->user_id != $user->id) {
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    if (!$dryRun) {
                        $existingMember->update([
                            'student_id' => $student->id,
                            'user_id' => $user->id,
                        ]);
                        
                        // Also update student's user_id if not set
                        if (!$student->user_id) {
                            $student->update(['user_id' => $user->id]);
                        }
                    }
                    $updated++;
                    $results[] = [
                        'student_id' => $student->id,
                        'student_code' => $studentCode,
                        'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                        'status' => 'UPDATED',
                        'user_email' => $user->email,
                        'academy_member_id' => $existingMember->id,
                    ];

                    if ($detailed) {
                        $this->newLine();
                        $this->info("Updated AcademyMember ID: {$existingMember->id} with student_id: {$student->id}, user_id: {$user->id}");
                    }
                } else {
                    $alreadyAssigned++;
                    $results[] = [
                        'student_id' => $student->id,
                        'student_code' => $studentCode,
                        'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                        'status' => 'ALREADY_ASSIGNED',
                        'user_email' => $user->email,
                        'academy_member_id' => $existingMember->id,
                    ];

                    if ($detailed) {
                        $this->newLine();
                        $this->line("Already assigned: AcademyMember ID: {$existingMember->id}");
                    }
                }
            } else {
                // Check if there's a wrong record (user_id=1, academy_id=1) that should be updated
                $wrongMember = AcademyMember::where('user_id', 1)
                    ->where('academy_id', 1)
                    ->where('member_code', $studentCode)
                    ->first();

                if ($wrongMember) {
                    if (!$dryRun) {
                        $wrongMember->update([
                            'academy_id' => $targetAcademyId,
                            'user_id' => $user->id,
                            'student_id' => $student->id,
                        ]);
                        
                        // Also update student's user_id if not set
                        if (!$student->user_id) {
                            $student->update(['user_id' => $user->id]);
                        }
                    }
                    $updated++;
                    $results[] = [
                        'student_id' => $student->id,
                        'student_code' => $studentCode,
                        'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                        'status' => 'FIXED_WRONG_RECORD',
                        'user_email' => $user->email,
                        'academy_member_id' => $wrongMember->id,
                    ];

                    if ($detailed) {
                        $this->newLine();
                        $this->info("Fixed wrong record - AcademyMember ID: {$wrongMember->id}");
                    }
                } else {
                    // Create new AcademyMember record
                    if (!$dryRun) {
                        try {
                            $newMember = AcademyMember::create([
                                'academy_id' => $targetAcademyId,
                                'user_id' => $user->id,
                                'student_id' => $student->id,
                                'member_code' => $studentCode,
                                'status' => 2, // member status
                                'enrollment_date' => $student->enrollment_date ?? now(),
                            ]);

                            // Also update student's user_id if not set
                            if (!$student->user_id) {
                                $student->update(['user_id' => $user->id]);
                            }

                            $created++;
                            $results[] = [
                                'student_id' => $student->id,
                                'student_code' => $studentCode,
                                'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                                'status' => 'CREATED',
                                'user_email' => $user->email,
                                'academy_member_id' => $newMember->id,
                            ];

                            if ($detailed) {
                                $this->newLine();
                                $this->info("Created new AcademyMember ID: {$newMember->id}");
                            }
                        } catch (\Exception $e) {
                            $errors++;
                            $results[] = [
                                'student_id' => $student->id,
                                'student_code' => $studentCode,
                                'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                                'status' => 'ERROR',
                                'error' => $e->getMessage(),
                            ];

                            if ($detailed) {
                                $this->newLine();
                                $this->error("Error creating AcademyMember: " . $e->getMessage());
                            }
                            Log::error("FixAcademyMemberAssignment Error", [
                                'student_id' => $student->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        $created++;
                        $results[] = [
                            'student_id' => $student->id,
                            'student_code' => $studentCode,
                            'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                            'status' => 'WILL_CREATE',
                            'user_email' => $user->email,
                        ];
                    }
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Students', $students->count()],
                ['Matched with Users', $matched],
                ['No User Found', $notMatched],
                ['Already Correctly Assigned', $alreadyAssigned],
                ['Updated', $updated],
                ['Created', $created],
                ['Errors', $errors],
            ]
        );

        // Show detailed results if verbose
        if ($detailed && !empty($results)) {
            $this->newLine();
            $this->info('=== Detailed Results ===');
            $this->table(
                ['Student ID', 'Code', 'Name', 'Status', 'User Email'],
                collect($results)->map(function ($r) {
                    return [
                        $r['student_id'],
                        $r['student_code'],
                        $r['student_name'] ?? '-',
                        $r['status'],
                        $r['user_email'] ?? '-',
                    ];
                })->toArray()
            );
        }

        // Export unmatched students for manual review
        $unmatchedStudents = collect($results)->where('status', 'NO_USER_FOUND');
        if ($unmatchedStudents->count() > 0) {
            $this->newLine();
            $this->warn("There are {$unmatchedStudents->count()} students without matching users.");
            
            if ($this->confirm('Do you want to export unmatched students to a CSV file?')) {
                $filename = storage_path('app/unmatched_students_' . date('Y-m-d_His') . '.csv');
                $fp = fopen($filename, 'w');
                fputcsv($fp, ['Student ID', 'Student Code', 'Student Name']);
                foreach ($unmatchedStudents as $student) {
                    fputcsv($fp, [
                        $student['student_id'],
                        $student['student_code'],
                        $student['student_name'] ?? '',
                    ]);
                }
                fclose($fp);
                $this->info("Exported to: {$filename}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return 0;
    }
}
