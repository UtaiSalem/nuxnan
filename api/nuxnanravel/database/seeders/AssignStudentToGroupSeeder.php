<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignStudentToGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Find the target Academy
        $academy = \App\Models\Academy::where('name', 'LIKE', '%Plearnd Wittayathan%')
                    ->orWhere('name', 'LIKE', '%เพลินวิทยาทาน%')
                    ->first();

        if (!$academy) {
            $this->command->error("Academy not found. Using ID 1.");
            $academy = \App\Models\Academy::find(1);
        }

        if (!$academy) {
            $this->command->error("No Academy found.");
            return;
        }

        $this->command->info("Assigning students for Academy: {$academy->name}");

        // 2. Fetch all valid Student Academic Infos (with valid classroom)
        // Adjust query to handle potential duplicates or non-current if needed, but 'is_current' is best if data exists
        $academicInfos = \App\Models\StudentAcademicInfo::whereNotNull('classroom_full')
                            ->where('classroom_full', '!=', '')
                            ->with('student')
                            ->get();

        $count = 0;
        $skipped = 0;

        foreach ($academicInfos as $info) {
            $student = $info->student;
            if (!$student) continue;

            // Find the group matches the classroom
            $group = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                        ->where('name', $info->classroom_full)
                        ->first();

            if ($group) {
                // Check if already a member (either by user_id or student_id)
                $query = \DB::table('academy_group_members')
                            ->where('academy_group_id', $group->id);
                
                if ($student->user_id) {
                    $query->where('user_id', $student->user_id);
                } else {
                    $query->where('student_id', $student->id);
                }
                
                $exists = $query->exists();

                if (!$exists) {
                    \DB::table('academy_group_members')->insert([
                        'academy_group_id' => $group->id,
                        'user_id' => $student->user_id, // Can be null
                        'student_id' => $student->id,   // Can be null if only user, but here we have student
                        'role' => 'student',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count++;
                }
            } else {
                //  $this->command->warn("Group for classroom '{$info->classroom_full}' not found.");
            }
        }

        $this->command->info("Assigned {$count} students to groups. Skipped {$skipped} students (no user_id).");
    }
}
