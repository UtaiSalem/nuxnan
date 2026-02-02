<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixAcademyMemberUserIdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get all students that have a student_id
        $students = \App\Models\Student::whereNotNull('student_id')->get();
        $academyId = 1;

        $updatedCount = 0;
        $notFoundCount = 0;

        foreach ($students as $student) {
            $studentId = trim($student->student_id);
            if (empty($studentId)) continue;

            // 2. Find User by Email
            $email = "s{$studentId}@jariyathum.ac.th";
            $user = \App\Models\User::where('email', $email)->first();

            if ($user) {
                // 3. Update Academy Member
                $member = \App\Models\AcademyMember::where('academy_id', $academyId)
                    ->where('student_id', $student->id)
                    ->first();

                if ($member) {
                    if ($member->user_id !== $user->id) {
                        $member->update(['user_id' => $user->id]);
                        $updatedCount++;
                    }
                } else {
                     // Create if missing (Safety net)
                    \App\Models\AcademyMember::create([
                        'academy_id' => $academyId,
                        'user_id' => $user->id,
                        'student_id' => $student->id,
                        'member_code' => $studentId,
                        'status' => 2, // Active
                        'role' => 'student',
                        'enrollment_date' => now(),
                    ]);
                    $updatedCount++;
                }

                // Ensure Student record is also linked correctly
                if ($student->user_id !== $user->id) {
                    $student->update(['user_id' => $user->id]);
                }

            } else {
                // Option A: Create User from Student Data
                try {
                    $name = trim($student->first_name_th . ' ' . $student->last_name_th);
                    if (empty($name)) {
                        $name = $student->full_name_th ?: "Student {$studentId}";
                    }

                    // Create New User
                    $user = \App\Models\User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => \Hash::make("s{$studentId}000"), // Default password pattern
                        'reference_code' => \Illuminate\Support\Str::random(10),
                        'personal_code' => \App\Models\User::generateReferralCode(),
                        'email_verified_at' => now(),
                    ]);

                    // Link Student to new User
                    $student->update(['user_id' => $user->id]);

                    // Create/Update Academy Member
                    $member = \App\Models\AcademyMember::updateOrCreate(
                        ['academy_id' => $academyId, 'student_id' => $student->id],
                        [
                            'user_id' => $user->id,
                            'member_code' => $studentId,
                            'status' => 2,
                            'role' => 'student',
                            'enrollment_date' => now(), 
                        ]
                    );

                    $updatedCount++;
                    $this->command->info("Created User for Student ID: {$studentId}");

                } catch (\Exception $e) {
                     $this->command->error("Failed to create user for Student ID {$studentId}: " . $e->getMessage());
                }
            }
        }

        $this->command->info("Fix Complete: Updated {$updatedCount} Academy Members. Reference User Not Found: {$notFoundCount}.");
    }
}
