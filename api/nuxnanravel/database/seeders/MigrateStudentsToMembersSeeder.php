<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateStudentsToMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Academy (ID 1 = โรงเรียนเพลินวิทยาธาร)
        $academy = Academy::find(1);

        if (! $academy) {
            $this->command->error('Academy not found!');

            return;
        }

        $this->command->info("Migrating students to Academy: {$academy->name} (ID: {$academy->id})");

        // Get all students
        $students = Student::all();
        $this->command->info("Found {$students->count()} students to migrate");

        $created = 0;
        $updated = 0;

        foreach ($students as $student) {
            // Check if member already exists
            $existingMember = DB::table('academy_members')
                ->where('academy_id', $academy->id)
                ->where(function ($q) use ($student) {
                    if ($student->user_id) {
                        $q->where('user_id', $student->user_id);
                    } else {
                        $q->where('student_id', $student->id);
                    }
                })
                ->first();

            $memberData = [
                'academy_id' => $academy->id,
                'user_id' => $student->user_id ?: null,
                'student_id' => $student->id,
                'role' => 'student',
                'member_code' => $student->student_id ?: null, // Handle empty student_id
                'status' => 2, // 2 = approved member
                'updated_at' => now(),
            ];

            if ($existingMember) {
                DB::table('academy_members')
                    ->where('id', $existingMember->id)
                    ->update($memberData);
                $updated++;
            } else {
                $memberData['created_at'] = now();
                DB::table('academy_members')->insert($memberData);
                $created++;
            }

            // Progress update every 500 records
            if (($created + $updated) % 500 == 0) {
                $this->command->info('Processed '.($created + $updated).' students...');
            }
        }

        // Skip updating academy_id in students table if column doesn't exist
        if (Schema::hasColumn('students', 'academy_id')) {
            Student::whereNull('academy_id')->update(['academy_id' => $academy->id]);
        }

        // Update total_students count for academy
        $totalApprovedMembers = DB::table('academy_members')
            ->where('academy_id', $academy->id)
            ->where('status', 2)
            ->count();
        $academy->update(['total_students' => $totalApprovedMembers]);

        $this->command->info('Migration complete!');
        $this->command->info("Created: {$created} new members");
        $this->command->info("Updated: {$updated} existing members");
        $this->command->info('Total: '.($created + $updated).' records');
        $this->command->info("Academy total_students updated to: {$totalApprovedMembers}");
    }
}
