<?php

namespace Database\Seeders;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportStudentsFromCardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cards = \DB::table('student_cards')->whereNotNull('student_number')->get();
        $academyId = 1;

        $createdCount = 0;
        $linkedCount = 0;
        $enrolledCount = 0;

        foreach ($cards as $card) {
            $studentNumber = trim($card->student_number);
            $fullNameThai = trim($card->full_name_thai);

            if (empty($studentNumber)) {
                continue;
            }

            $email = "s{$studentNumber}@jariyathum.ac.th";

            // 1. Find or Create User
            // Check by Email first
            $user = User::where('email', $email)->first();

            if (! $user) {
                // Check by Normalized Name to avoid duplicate people with different emails
                $normalizedCardName = $this->normalizeName($fullNameThai);

                // Fetch all users and filter in PHP (or use advanced SQL if performant enough)
                // For safety and speed in Seeder, likely okay to check exact matches first
                // Or loop through potential matches.
                // Optimally:
                $user = User::all()->first(function ($u) use ($normalizedCardName) {
                    return $this->normalizeName($u->name) === $normalizedCardName;
                });
            }

            if (! $user) {
                // Create New User
                try {
                    $refCode = $this->generateUniqueReferenceCode();
                    $personalCode = User::generateReferralCode();

                    $user = User::create([
                        'name' => $fullNameThai ?: "Student {$studentNumber}",
                        'email' => $email,
                        'password' => \Hash::make("s{$studentNumber}000"),
                        'reference_code' => $refCode, // Use random 8-digit code
                        'personal_code' => $personalCode,
                        'email_verified_at' => now(),
                    ]);
                    $createdCount++;
                } catch (\Exception $e) {
                    \Log::error("Failed to create user for {$studentNumber}: ".$e->getMessage());
                    $this->command->error("Failed user {$studentNumber}: ".$e->getMessage());

                    continue;
                }
            } else {
                // Update reference code if using old format (student number or 8-digit random)
                if (strlen($user->reference_code) !== 10) {
                    $user->update(['reference_code' => $this->generateUniqueReferenceCode()]);
                }
            }

            // 2. Sync with Student Table
            // Find Student Record
            $student = Student::where('student_id', $studentNumber)->first();

            if ($student) {
                // Update existing student with user_id
                if ($student->user_id !== $user->id) {
                    $student->update(['user_id' => $user->id]);
                    $linkedCount++;
                }
            } else {
                // Create Student Record checking from Cards
                try {
                    $student = Student::create([
                        'student_id' => $studentNumber,
                        'user_id' => $user->id,
                        'academy_id' => $academyId,
                        'first_name_th' => $card->first_name_thai,
                        'last_name_th' => $card->last_name_thai,
                        'full_name_th' => $fullNameThai,
                        'status' => 'active',
                    ]);
                    // Update names if available
                    if (! empty($card->first_name_thai)) {
                        $student->update([
                            'first_name_th' => $card->first_name_thai,
                            'last_name_th' => $card->last_name_thai,
                            'title_prefix_th' => $card->title_name,
                        ]);
                    }
                    $linkedCount++;
                } catch (\Exception $e) {
                    \Log::error("Failed to create student record for {$studentNumber}: ".$e->getMessage());
                }
            }

            // 3. Enroll in Academy
            if ($student) {
                $isMember = AcademyMember::where('academy_id', $academyId)
                    ->where('student_id', $student->id)
                    ->exists();

                if (! $isMember) {
                    AcademyMember::create([
                        'academy_id' => $academyId,
                        'user_id' => $user->id,
                        'student_id' => $student->id,
                        'member_code' => $studentNumber,
                        'status' => 2, // Active
                        'role' => 'student',
                        'enrollment_date' => now(),
                    ]);
                    $enrolledCount++;
                }
            }
        }

        $this->command->info("Import Complete: Created {$createdCount} Users, Linked {$linkedCount} Students, Enrolled {$enrolledCount} Members.");
    }

    private function normalizeName($name)
    {
        if (empty($name)) {
            return '';
        }

        // Remove spaces
        $name = str_replace([
            ' ', '.',
            'เด็กชาย', 'เด็กหญิง', 'ด.ช.', 'ด.ญ.',
            'นาย', 'นางสาว', 'นาง',
            'Mr.', 'Mrs.', 'Miss.', 'Miss', 'Master',
        ], '', $name);

        return trim($name);
    }

    private function generateUniqueReferenceCode()
    {
        do {
            $code = Str::random(10);
        } while (User::where('reference_code', $code)->exists());

        return $code;
    }
}
