<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ============================================================================
 * Command: Reset Student Passwords
 * ============================================================================
 *
 * รีเซ็ตรหัสผ่านของ users ที่มี email ตามรูปแบบ S{student_code}@domain
 * ให้เป็นรหัสผ่านตามรูปแบบมาตรฐาน
 *
 * ============================================================================
 */
class ResetStudentPasswords extends Command
{
    protected $signature = 'students:reset-passwords 
                            {--email-pattern=s%@jariyathum.ac.th : Email pattern to match (SQL LIKE)}
                            {--password-pattern=s[code]000 : Password pattern ([code] = extracted from email)}
                            {--dry-run : Preview without making changes}
                            {--limit= : Limit number of records to process}';

    protected $description = 'Reset passwords for users with student email pattern to standard format';

    protected $codePlaceholder = '[code]'; // Placeholder for student code

    public function handle()
    {
        $emailPattern = $this->option('email-pattern');
        $passwordPattern = $this->option('password-pattern');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->displayHeader($emailPattern, $passwordPattern, $dryRun);

        // =====================================================================
        // Step 1: Get Users with matching email pattern
        // =====================================================================
        $this->info('📖 Step 1: Finding users with matching email pattern...');

        $query = User::whereRaw('LOWER(email) LIKE ?', [strtolower($emailPattern)]);

        if ($limit) {
            $query->limit((int) $limit);
        }

        $users = $query->get();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->warn('No users found matching the email pattern.');

            return Command::SUCCESS;
        }

        $this->info("   Found {$totalUsers} users to process");
        $this->newLine();

        // =====================================================================
        // Step 2: Reset Passwords
        // =====================================================================
        $this->info('🔄 Step 2: Resetting passwords...');

        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $errorLog = [];
        $sampleUpdates = [];

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                try {
                    // Extract student code from email
                    // Pattern: S{code}@domain → extract {code}
                    $studentCode = $this->extractStudentCode($user->email);

                    if (empty($studentCode)) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Generate new password
                    $newPassword = $this->generatePassword($studentCode, $passwordPattern);

                    if (! $dryRun) {
                        $user->update([
                            'password' => Hash::make($newPassword),
                        ]);
                    }

                    $updated++;

                    // Keep first 5 for sample display
                    if (count($sampleUpdates) < 5) {
                        $sampleUpdates[] = [
                            'id' => $user->id,
                            'email' => $user->email,
                            'student_code' => $studentCode,
                            'new_password' => $newPassword,
                        ];
                    }

                } catch (\Exception $e) {
                    $errors++;
                    if (count($errorLog) < 10) {
                        $errorLog[] = [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ];
                    }
                }

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
            $this->error('Transaction rolled back: '.$e->getMessage());

            return Command::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        // =====================================================================
        // Step 3: Display Results
        // =====================================================================
        $this->displaySummary($totalUsers, $updated, $skipped, $errors, $dryRun);

        if (count($sampleUpdates) > 0) {
            $this->newLine();
            $this->info('📋 Sample of password resets:');
            $this->table(
                ['User ID', 'Email', 'Student Code', 'New Password'],
                array_map(function ($item) {
                    return [
                        $item['id'],
                        $item['email'],
                        $item['student_code'],
                        $item['new_password'],
                    ];
                }, $sampleUpdates)
            );
        }

        if (count($errorLog) > 0) {
            $this->newLine();
            $this->warn('⚠️ Errors encountered:');
            $this->table(
                ['User ID', 'Email', 'Error'],
                array_map(function ($e) {
                    return [$e['user_id'], $e['email'], substr($e['error'], 0, 50)];
                }, $errorLog)
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Extract student code from email
     * s12345@jariyathum.ac.th → 12345
     */
    protected function extractStudentCode(string $email): ?string
    {
        // Match pattern: s{digits}@
        if (preg_match('/^s(\d+)@/i', $email, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Generate password from pattern
     * Pattern: s[code]000 → s12345000
     */
    protected function generatePassword(string $studentCode, string $pattern): string
    {
        return str_replace('[code]', $studentCode, $pattern);
    }

    protected function displayHeader(string $emailPattern, string $passwordPattern, bool $dryRun)
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║     STUDENT PASSWORD RESET TOOL                               ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Email Pattern', $emailPattern],
                ['Password Pattern', $passwordPattern],
                ['Example', 's12345@jariyathum.ac.th → s12345000'],
            ]
        );
        $this->newLine();
    }

    protected function displaySummary(int $total, int $updated, int $skipped, int $errors, bool $dryRun)
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📋 SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');

        $this->table(
            ['Status', 'Count'],
            [
                ['Total Users Found', $total],
                ['✅ Passwords Reset', $updated],
                ['⏭️ Skipped (Invalid Pattern)', $skipped],
                ['❌ Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No passwords were changed.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Password reset completed!');
            $this->warn('⚠️  Students should change their password after first login.');
        }
    }
}
