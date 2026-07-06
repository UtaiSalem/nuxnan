<?php

namespace App\Console\Commands;

use App\Models\AcademyMember;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateAcademyMemberUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academy:update-user-ids 
                            {--academy= : Filter by specific academy ID}
                            {--dry-run : Run without actually updating the database}
                            {--skip-email : Skip email matching, only use course_members matching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update User IDs in Academy Members table by matching member_code with User email and course_members';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $academyId = $this->option('academy');
        $skipEmail = $this->option('skip-email');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $this->info('🔍 Starting Academy Member User ID Update...');
        $this->newLine();

        // Get academy members with member_code that don't have user_id
        $query = AcademyMember::whereNotNull('member_code')
            ->where('member_code', '!=', '')
            ->whereNull('user_id');

        if ($academyId) {
            $query->where('academy_id', $academyId);
            $this->info("Filtering by Academy ID: {$academyId}");
        }

        $members = $query->get();
        $totalMembers = $members->count();

        $this->info("📊 Found {$totalMembers} Academy Members with member_code but no user_id");
        $this->newLine();

        if ($totalMembers === 0) {
            $this->info('✅ All academy members with member_code already have user_id.');

            return Command::SUCCESS;
        }

        $updatedByEmail = 0;
        $updatedByCourseMember = 0;
        $notFound = 0;
        $errors = 0;
        $notFoundList = [];

        // ============================================
        // PASS 1: Match by Email Pattern (S{member_code}@jariyathum.ac.th)
        // ============================================
        if (! $skipEmail) {
            $this->info('📧 Pass 1: Matching by email pattern...');
            $bar = $this->output->createProgressBar($totalMembers);
            $bar->start();

            foreach ($members as $member) {
                try {
                    $memberCode = trim((string) $member->member_code);

                    if (empty($memberCode)) {
                        $bar->advance();

                        continue;
                    }

                    // Construct email from member_code
                    // Format: S + member_code + @jariyathum.ac.th
                    $email = "S{$memberCode}@jariyathum.ac.th";

                    // Try case-insensitive search
                    $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

                    if ($user) {
                        if (! $dryRun) {
                            $member->update(['user_id' => $user->id]);
                        }
                        $updatedByEmail++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("   ✅ Updated by email: {$updatedByEmail}");
        }

        // ============================================
        // PASS 2: Match by course_members.member_code
        // ============================================
        $this->newLine();
        $this->info('📚 Pass 2: Matching by course_members.member_code...');

        // Refresh query to get remaining members without user_id
        $remainingQuery = AcademyMember::whereNotNull('member_code')
            ->where('member_code', '!=', '')
            ->whereNull('user_id');

        if ($academyId) {
            $remainingQuery->where('academy_id', $academyId);
        }

        $remainingMembers = $remainingQuery->get();
        $remainingCount = $remainingMembers->count();

        $this->info("   Found {$remainingCount} members still without user_id");

        if ($remainingCount > 0) {
            $bar = $this->output->createProgressBar($remainingCount);
            $bar->start();

            foreach ($remainingMembers as $member) {
                try {
                    $memberCode = trim((string) $member->member_code);

                    if (empty($memberCode)) {
                        $bar->advance();

                        continue;
                    }

                    // Find course_member with matching member_code and valid user_id
                    $courseMember = CourseMember::where('member_code', $memberCode)
                        ->whereNotNull('user_id')
                        ->first();

                    if ($courseMember && $courseMember->user_id) {
                        if (! $dryRun) {
                            $member->update(['user_id' => $courseMember->user_id]);
                        }
                        $updatedByCourseMember++;
                    } else {
                        $notFound++;
                        if (count($notFoundList) < 20) {
                            $notFoundList[] = [
                                'member_id' => $member->id,
                                'member_code' => $memberCode,
                                'current_user_id' => $member->user_id,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing member ID {$member->id}: ".$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("   ✅ Updated by course_members: {$updatedByCourseMember}");
        }

        $this->newLine();

        // Summary
        $totalUpdated = $updatedByEmail + $updatedByCourseMember;

        $this->info('═══════════════════════════════════════════');
        $this->info('📋 SUMMARY');
        $this->info('═══════════════════════════════════════════');

        $this->table(
            ['Status', 'Count'],
            [
                ['Total Members Processed', $totalMembers],
                ['✅ Updated by Email', $updatedByEmail],
                ['✅ Updated by Course Members', $updatedByCourseMember],
                ['📊 Total Updated', $totalUpdated],
                ['❌ Still Not Found', $notFound],
                ['⚠️ Errors', $errors],
            ]
        );

        if ($notFound > 0 && count($notFoundList) > 0) {
            $this->newLine();
            $this->warn('📌 Sample of members where user was not found (max 20):');
            $this->table(
                ['Member ID', 'Member Code', 'Current User ID'],
                array_map(function ($item) {
                    return [
                        $item['member_id'],
                        $item['member_code'],
                        $item['current_user_id'] ?? 'NULL',
                    ];
                }, $notFoundList)
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Update completed!');
        }

        return Command::SUCCESS;
    }
}
