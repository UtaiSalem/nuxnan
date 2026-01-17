<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademyGroupFromStudentSeeder extends Seeder
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
            $this->command->error("Academy 'Plearnd Wittayathan' not found. Using ID 1 as fallback.");
            $academy = \App\Models\Academy::find(1);
        }

        if (!$academy) {
            $this->command->error("No Academy found to seed.");
            return;
        }

        $this->command->info("Seeding groups for Academy: {$academy->name} (ID: {$academy->id})");

        // 2. Get unique classrooms from StudentAcademicInfo
        // We look for 'is_current' records ideally, or all records.
        // Grouping by 'classroom_full' which seems to be the formatted string "M.1/1" etc.
        $classrooms = \App\Models\StudentAcademicInfo::whereNotNull('classroom_full')
                        ->where('classroom_full', '!=', '')
                        ->distinct()
                        ->pluck('classroom_full');

        $count = 0;
        foreach ($classrooms as $roomName) {
            // Check if group already exists
            $exists = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                        ->where('name', $roomName)
                        ->exists();

            if (!$exists) {
                \App\Models\AcademyGroup::create([
                    'academy_id' => $academy->id,
                    'name' => $roomName,
                    'description' => "Classroom {$roomName} generated from student records.",
                    'type' => 'classroom',
                    'settings' => json_encode(['auto_generated' => true]),
                ]);
                $count++;
            }
        }

        $this->command->info("Created {$count} new Academy Groups (Classrooms).");
        
        // Extended Step: Assign Students to Groups?
        // The user request was "create as groups of the school". 
        // Assigning members might be the next logical step but "create as groups" is the primary instruction.
    }
}
