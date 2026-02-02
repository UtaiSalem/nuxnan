<?php

namespace App\Console\Commands;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseAcademyMemberIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academy:diagnose-members 
                            {--academy= : Specific academy ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose Academy Member data issues - find records with incorrect user_id and academy_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $academyId = $this->option('academy');

        $this->info('=== Academy Member Data Diagnosis ===');
        $this->newLine();

        // 1. Find records with user_id = 1 and academy_id = 1 (problematic records)
        $problematicRecords = AcademyMember::where('user_id', 1)
            ->where('academy_id', 1)
            ->count();

        $this->info("1. Problematic Records (user_id=1, academy_id=1): {$problematicRecords}");

        if ($problematicRecords > 0) {
            $samples = AcademyMember::where('user_id', 1)
                ->where('academy_id', 1)
                ->limit(10)
                ->get(['id', 'user_id', 'academy_id', 'student_id', 'member_code', 'status']);

            $this->table(
                ['ID', 'User ID', 'Academy ID', 'Student ID', 'Member Code', 'Status'],
                $samples->map(function ($m) {
                    return [$m->id, $m->user_id, $m->academy_id, $m->student_id, $m->member_code, $m->status];
                })->toArray()
            );

            if ($problematicRecords > 10) {
                $this->line("... and " . ($problematicRecords - 10) . " more records");
            }
        }

        $this->newLine();

        // 2. Count total students
        $studentsQuery = Student::query();
        if ($academyId) {
            $studentsQuery->where('academy_id', $academyId);
        }
        $totalStudents = $studentsQuery->count();
        $this->info("2. Total Students: {$totalStudents}");

        // 3. Count students with user_id assigned
        $studentsWithUser = Student::whereNotNull('user_id')
            ->when($academyId, fn($q) => $q->where('academy_id', $academyId))
            ->count();
        $this->info("3. Students with user_id assigned: {$studentsWithUser}");

        // 4. Count students without user_id
        $studentsWithoutUser = Student::whereNull('user_id')
            ->when($academyId, fn($q) => $q->where('academy_id', $academyId))
            ->count();
        $this->info("4. Students without user_id: {$studentsWithoutUser}");

        $this->newLine();

        // 5. Check potential email matches
        $this->info("5. Checking potential email matches...");
        
        $students = Student::whereNull('user_id')
            ->when($academyId, fn($q) => $q->where('academy_id', $academyId))
            ->limit(100)
            ->get();

        $matchCount = 0;
        $matches = [];

        foreach ($students as $student) {
            $studentCode = $student->student_id;
            
            if (empty($studentCode)) {
                continue;
            }

            $user = User::where('email', 'LIKE', "{$studentCode}%")
                ->orWhere('email', 'LIKE', "%{$studentCode}@%")
                ->orWhere('email', 'LIKE', "%_{$studentCode}@%")
                ->first();

            if ($user) {
                $matchCount++;
                $matches[] = [
                    'student_id' => $student->id,
                    'student_code' => $studentCode,
                    'student_name' => $student->first_name_th . ' ' . $student->last_name_th,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                ];
            }
        }

        $this->info("   Found {$matchCount} potential matches (from first 100 students without user_id)");

        if (!empty($matches)) {
            $this->newLine();
            $this->info("   Sample matches:");
            $this->table(
                ['Student ID', 'Code', 'Student Name', 'User ID', 'User Email'],
                collect($matches)->take(10)->map(function ($m) {
                    return [
                        $m['student_id'],
                        $m['student_code'],
                        $m['student_name'],
                        $m['user_id'],
                        $m['user_email'],
                    ];
                })->toArray()
            );
        }

        $this->newLine();

        // 6. Show academy distribution of problematic records
        $this->info("6. Distribution of problematic records by member_code patterns:");
        
        $memberCodePatterns = AcademyMember::where('user_id', 1)
            ->where('academy_id', 1)
            ->selectRaw("SUBSTRING(member_code, 1, 2) as prefix, COUNT(*) as count")
            ->groupBy('prefix')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        if ($memberCodePatterns->count() > 0) {
            $this->table(
                ['Member Code Prefix', 'Count'],
                $memberCodePatterns->map(function ($p) {
                    return [$p->prefix, $p->count];
                })->toArray()
            );
        }

        $this->newLine();

        // Recommendations
        $this->info('=== Recommendations ===');
        
        if ($problematicRecords > 0) {
            $this->warn("⚠ You have {$problematicRecords} records with user_id=1 and academy_id=1");
            $this->line("   Run: php artisan academy:fix-member-assignment --dry-run");
            $this->line("   to see what would be fixed, then run without --dry-run to apply.");
        }

        if ($matchCount > 0) {
            $this->info("✓ Found {$matchCount} students that can be matched with users by email pattern");
        }

        return 0;
    }
}
