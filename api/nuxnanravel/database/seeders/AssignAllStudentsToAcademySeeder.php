<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignAllStudentsToAcademySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Find Academy (Plearnd Wittayathan - ID 1)
        $academyId = 1;
        $academy = \App\Models\Academy::find($academyId);

        if (!$academy) {
             // Try to find by name if ID 1 fails (unlikely based on previous steps)
            $academy = \App\Models\Academy::where('name', 'LIKE', '%Plearnd Wittayathan%')
                    ->orWhere('name', 'LIKE', '%เพลินวิทยาทาน%')
                    ->first();
        }

        if (!$academy) {
            $this->command->error("Academy not found.");
            return;
        }

        $this->command->info("Assigning all students to Academy: {$academy->name} (ID: {$academy->id})");

        // 2. Update students table (bulk update)
        \App\Models\Student::whereNull('academy_id')->update(['academy_id' => $academy->id]);
        $this->command->info("Updated 'students' table academy_id.");

        // 3. Create Academy Members records
        $students = \App\Models\Student::all();
        $count = 0;

        foreach ($students as $student) {
            $key = ['academy_id' => $academy->id];
            if ($student->user_id) {
                $key['user_id'] = $student->user_id;
            } else {
                $key['student_id'] = $student->id;
            }

            \DB::table('academy_members')->updateOrInsert(
                $key,
                [
                    'user_id' => $student->user_id, // Ensure both are set correctly
                    'student_id' => $student->id,
                    'role' => 'student',
                    'member_code' => $student->student_id, 
                    'status' => 1, // Active
                    'updated_at' => now(),
                    // Note: 'created_at' => now() won't be used by update portion if updating
                ]
            );
            $count++;
        }

        $this->command->info("Created {$count} new Academy Member records.");
    }
}
