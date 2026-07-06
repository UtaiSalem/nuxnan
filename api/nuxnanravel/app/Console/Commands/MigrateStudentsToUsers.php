<?php

namespace App\Console\Commands;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * Command: Migrate Students to Users and Academy Members
 * ============================================================================
 *
 * คำสั่งนี้ใช้สำหรับย้ายข้อมูลจากตาราง students หรือ student_cards
 * ไปสร้างระเบียนใหม่ในตาราง users และ academy_members
 *
 * การทำงาน:
 * 1. อ่านข้อมูลจาก students/student_cards ที่ยังไม่มี user_id
 * 2. ตรวจสอบความซ้ำซ้อนก่อนสร้าง User ใหม่
 * 3. สร้าง User ใหม่พร้อมเข้ารหัสรหัสผ่าน
 * 4. อัปเดต/สร้าง AcademyMember พร้อมเชื่อมโยง user_id
 * 5. อัปเดต Student.user_id
 *
 * ============================================================================
 */
class MigrateStudentsToUsers extends Command
{
    protected $signature = 'students:migrate-to-users 
                            {--source=students : Source table (students or student_cards)}
                            {--academy= : Academy ID to assign members to}
                            {--email-domain=jariyathum.ac.th : Email domain for generated emails}
                            {--password-pattern=s[code]000 : Password pattern ([code] = student_id)}
                            {--dry-run : Preview without making changes}
                            {--limit= : Limit number of records to process}';

    protected $description = 'Migrate student data to create users and academy members';

    // Statistics
    protected $stats = [
        'total_processed' => 0,
        'users_created' => 0,
        'users_skipped_duplicate' => 0,
        'academy_members_created' => 0,
        'academy_members_updated' => 0,
        'students_updated' => 0,
        'errors' => 0,
    ];

    protected $errorLog = [];

    public function handle()
    {
        $source = $this->option('source');
        $academyId = $this->option('academy') ?? 1; // Default academy ID
        $emailDomain = $this->option('email-domain');
        $passwordPattern = $this->option('password-pattern');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->displayHeader($source, $academyId, $emailDomain, $dryRun);

        // =====================================================================
        // Step 1: Get Source Data
        // =====================================================================
        $this->info('📖 Step 1: Reading source data...');

        $records = $this->getSourceRecords($source, $limit);
        $totalRecords = $records->count();

        if ($totalRecords === 0) {
            $this->warn('No records found to migrate.');

            return Command::SUCCESS;
        }

        $this->info("   Found {$totalRecords} records to process");
        $this->newLine();

        // =====================================================================
        // Step 2: Process Each Record
        // =====================================================================
        $this->info('🔄 Step 2: Processing records...');
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($records as $record) {
                $this->processRecord($record, $source, $academyId, $emailDomain, $passwordPattern, $dryRun);
                $this->stats['total_processed']++;
                $bar->advance();
            }

            if (! $dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine(2);
            $this->error('Transaction rolled back due to error: '.$e->getMessage());

            return Command::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        // =====================================================================
        // Step 3: Display Results
        // =====================================================================
        $this->displaySummary($dryRun);

        if (count($this->errorLog) > 0) {
            $this->displayErrors();
        }

        return Command::SUCCESS;
    }

    /**
     * =========================================================================
     * Get Source Records
     * =========================================================================
     * ดึงข้อมูลจาก students หรือ student_cards
     * การตรวจสอบว่ามี user อยู่แล้วหรือยังจะทำใน processRecord โดยใช้ email
     */
    protected function getSourceRecords(string $source, ?int $limit)
    {
        if ($source === 'student_cards') {
            $query = StudentCard::query()
                ->whereNotNull('student_number')
                ->where('student_number', '!=', '');
        } else {
            // Default: students table
            // หมายเหตุ: ตาราง students ไม่มี column user_id
            // การตรวจสอบซ้ำจะทำใน processRecord โดยเช็คจาก email
            $query = Student::query()
                ->whereNotNull('student_id')
                ->where('student_id', '!=', '');
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * =========================================================================
     * Process Individual Record
     * =========================================================================
     */
    protected function processRecord($record, string $source, int $academyId, string $emailDomain, string $passwordPattern, bool $dryRun)
    {
        try {
            // Extract student code based on source
            $studentCode = $source === 'student_cards'
                ? trim($record->student_number)
                : trim($record->student_id);

            if (empty($studentCode)) {
                return;
            }

            // =====================================================
            // 2.1 Generate Email
            // =====================================================
            $email = $this->generateEmail($studentCode, $emailDomain);

            // =====================================================
            // 2.2 Check for Duplicate User (by email)
            // =====================================================
            $existingUser = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if ($existingUser) {
                // User already exists - just link to student/academy_member
                $this->stats['users_skipped_duplicate']++;
                $userId = $existingUser->id;
            } else {
                // =====================================================
                // 2.3 Create New User
                // =====================================================
                $name = $this->extractName($record, $source);
                $password = $this->generatePassword($studentCode, $passwordPattern);

                if (! $dryRun) {
                    $newUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password), // เข้ารหัสด้วย Bcrypt
                        'reference_code' => Str::random(10),
                        'personal_code' => User::generateReferralCode(),
                        'email_verified_at' => now(),
                        'verified' => true,
                    ]);
                    $userId = $newUser->id;
                } else {
                    $userId = 'NEW-'.$studentCode; // Placeholder for dry-run
                }
                $this->stats['users_created']++;
            }

            // =====================================================
            // 2.4 Update Student Record (skip - students table has no user_id column)
            // =====================================================
            // หมายเหตุ: ตาราง students ไม่มี column user_id จึงข้ามการอัปเดต
            // การเชื่อมโยงจะทำผ่าน academy_members แทน

            // =====================================================
            // 2.5 Create/Update Academy Member
            // =====================================================
            if (! $dryRun && is_numeric($userId)) {
                $this->createOrUpdateAcademyMember(
                    $academyId,
                    $userId,
                    $studentCode,
                    $source === 'students' ? $record->id : null
                );
            } elseif ($dryRun) {
                $this->stats['academy_members_created']++; // Assume created for dry-run
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->errorLog[] = [
                'record_id' => $record->id ?? 'unknown',
                'student_code' => $studentCode ?? 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * =========================================================================
     * Generate Email from Student Code
     * =========================================================================
     * Format: S{student_code}@{domain}
     */
    protected function generateEmail(string $studentCode, string $domain): string
    {
        return strtolower("s{$studentCode}@{$domain}");
    }

    /**
     * =========================================================================
     * Generate Password from Pattern
     * =========================================================================
     * Pattern: s[code]000 → s12345000
     */
    protected function generatePassword(string $studentCode, string $pattern): string
    {
        return str_replace('[code]', $studentCode, $pattern);
    }

    /**
     * =========================================================================
     * Extract Name from Record
     * =========================================================================
     */
    protected function extractName($record, string $source): string
    {
        if ($source === 'student_cards') {
            // From student_cards
            $name = trim(($record->first_name_thai ?? '').' '.($record->last_name_thai ?? ''));
            if (empty($name)) {
                $name = $record->full_name_thai ?? "Student {$record->student_number}";
            }
        } else {
            // From students
            $name = trim(($record->first_name_th ?? '').' '.($record->last_name_th ?? ''));
            if (empty($name)) {
                $name = trim(($record->first_name_en ?? '').' '.($record->last_name_en ?? ''));
            }
            if (empty($name)) {
                $name = "Student {$record->student_id}";
            }
        }

        return $name;
    }

    /**
     * =========================================================================
     * Create or Update Academy Member
     * =========================================================================
     */
    protected function createOrUpdateAcademyMember(int $academyId, int $userId, string $memberCode, ?int $studentId)
    {
        $existing = AcademyMember::where('academy_id', $academyId)
            ->where(function ($query) use ($userId, $memberCode, $studentId) {
                $query->where('user_id', $userId)
                    ->orWhere('member_code', $memberCode);
                if ($studentId) {
                    $query->orWhere('student_id', $studentId);
                }
            })
            ->first();

        if ($existing) {
            // Update existing record
            $existing->update([
                'user_id' => $userId,
                'member_code' => $memberCode,
                'student_id' => $studentId,
            ]);
            $this->stats['academy_members_updated']++;
        } else {
            // Create new record
            AcademyMember::create([
                'academy_id' => $academyId,
                'user_id' => $userId,
                'member_code' => $memberCode,
                'student_id' => $studentId,
                'status' => 2, // Active
                'role' => 'student',
                'enrollment_date' => now(),
            ]);
            $this->stats['academy_members_created']++;
        }
    }

    /**
     * =========================================================================
     * Display Header
     * =========================================================================
     */
    protected function displayHeader(string $source, int $academyId, string $emailDomain, bool $dryRun)
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║     STUDENT TO USER MIGRATION TOOL                             ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Source Table', $source],
                ['Academy ID', $academyId],
                ['Email Domain', $emailDomain],
                ['Email Format', "S{student_code}@{$emailDomain}"],
            ]
        );
        $this->newLine();
    }

    /**
     * =========================================================================
     * Display Summary
     * =========================================================================
     */
    protected function displaySummary(bool $dryRun)
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📋 MIGRATION SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');

        $this->table(
            ['Operation', 'Count'],
            [
                ['Total Records Processed', $this->stats['total_processed']],
                ['✅ Users Created', $this->stats['users_created']],
                ['⏭️ Users Skipped (Duplicate)', $this->stats['users_skipped_duplicate']],
                ['✅ Academy Members Created', $this->stats['academy_members_created']],
                ['🔄 Academy Members Updated', $this->stats['academy_members_updated']],
                ['❌ Errors', $this->stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Migration completed successfully!');
        }
    }

    /**
     * =========================================================================
     * Display Errors
     * =========================================================================
     */
    protected function displayErrors()
    {
        $this->newLine();
        $this->warn('⚠️ Errors encountered (up to 20 shown):');

        $errors = array_slice($this->errorLog, 0, 20);
        $this->table(
            ['Record ID', 'Student Code', 'Error'],
            array_map(function ($e) {
                return [$e['record_id'], $e['student_code'], Str::limit($e['error'], 50)];
            }, $errors)
        );
    }
}
