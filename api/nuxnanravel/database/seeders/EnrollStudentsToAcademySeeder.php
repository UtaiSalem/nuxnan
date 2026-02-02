<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnrollStudentsToAcademySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = \App\Models\Student::all();
        $academyId = 1;

        foreach ($students as $student) {
            // Check if already a member
            $exists = \App\Models\AcademyMember::where('academy_id', $academyId)
                ->where('student_id', $student->id)
                ->exists();

            if (!$exists) {
                \App\Models\AcademyMember::create([
                    'academy_id' => $academyId,
                    'user_id' => $student->user_id ?? 1, // Fallback to 1 or handle null if user_id is missing
                    'student_id' => $student->id,
                    'member_code' => $student->student_id, // Use student_id as member_code
                    'status' => 2, // Active
                    'role' => 'student',
                    'enrollment_date' => now(),
                ]);
            }
        }
    }
}
