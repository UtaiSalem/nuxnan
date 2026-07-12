<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeDuplicateClassrooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classrooms:merge {--commit : Actually apply changes to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and merge duplicate classrooms based on academy_id, academic_year_id, grade_level, and section';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commit = $this->option('commit');
        $this->info('Uniqueness Duplicate Classroom Merger');
        $this->info('Mode: '.($commit ? 'COMMIT (Live changes)' : 'DRY-RUN (Simulation)'));

        // 1. Find all duplicates
        $duplicateGroups = DB::table('classrooms')
            ->select('academy_id', 'academic_year_id', 'grade_level', 'section', DB::raw('COUNT(*) as cnt'))
            ->groupBy('academy_id', 'academic_year_id', 'grade_level', 'section')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate classrooms found.');

            return 0;
        }

        $this->info('Found '.$duplicateGroups->count().' groups of duplicate classrooms.');

        // 2. Fetch all foreign keys pointing to classrooms
        $dbName = DB::getDatabaseName();
        $foreignKeys = DB::select("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = 'classrooms'
              AND REFERENCED_TABLE_SCHEMA = ?
        ", [$dbName]);

        $this->info('Identified '.count($foreignKeys).' foreign key references pointing to classrooms.');

        foreach ($duplicateGroups as $group) {
            $this->line("\n========================================================");
            $this->info("Processing group: Academy {$group->academy_id} | Year ID {$group->academic_year_id} | {$group->grade_level}/{$group->section}");

            // Load classrooms in this group
            $rooms = DB::table('classrooms')
                ->where('academy_id', $group->academy_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('grade_level', $group->grade_level)
                ->where('section', $group->section)
                ->get();

            // Calculate score to pick survivor
            $scoredRooms = [];
            foreach ($rooms as $room) {
                $studentCount = DB::table('classroom_students')
                    ->where('classroom_id', $room->id)
                    ->where('status', 'active')
                    ->count();

                $memberCount = DB::table('classroom_members')
                    ->where('classroom_id', $room->id)
                    ->where('is_active', true)
                    ->count();

                $score = ($studentCount * 10) + $memberCount;
                $scoredRooms[] = [
                    'room' => $room,
                    'student_count' => $studentCount,
                    'member_count' => $memberCount,
                    'score' => $score,
                ];
            }

            // Sort scoredRooms: descending score, then ascending id (prefer oldest)
            usort($scoredRooms, function ($a, $b) {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                return $a['room']->id <=> $b['room']->id;
            });

            $survivor = $scoredRooms[0]['room'];
            $this->info("Survivor Chosen: ID {$survivor->id} (Code: {$survivor->classroom_code}) - Students: {$scoredRooms[0]['student_count']}, Members: {$scoredRooms[0]['member_count']}");

            $duplicatesToMerge = array_slice($scoredRooms, 1);

            foreach ($duplicatesToMerge as $dup) {
                $dupRoom = $dup['room'];
                $this->warn("Merging Duplicate Room: ID {$dupRoom->id} (Code: {$dupRoom->classroom_code}) - Students: {$dup['student_count']}, Members: {$dup['member_count']}");

                if (! $commit) {
                    $this->line("  [Dry Run] Would merge ID {$dupRoom->id} into Survivor ID {$survivor->id}");

                    continue;
                }

                DB::transaction(function () use ($survivor, $dupRoom, $foreignKeys) {
                    // Step A: Merge classroom_members (role/user_id unique)
                    $dupMembers = DB::table('classroom_members')
                        ->where('classroom_id', $dupRoom->id)
                        ->get();

                    foreach ($dupMembers as $dupMember) {
                        // Check if member already exists in survivor
                        $survivorMember = DB::table('classroom_members')
                            ->where('classroom_id', $survivor->id)
                            ->where('user_id', $dupMember->user_id)
                            ->where('role', $dupMember->role)
                            ->first();

                        if ($survivorMember) {
                            // Point any group_members pointing to duplicate member to the survivor member
                            // group_members has unique (group_id, member_id)
                            $groupMembers = DB::table('group_members')
                                ->where('member_id', $dupMember->id)
                                ->get();

                            foreach ($groupMembers as $gm) {
                                $exists = DB::table('group_members')
                                    ->where('group_id', $gm->group_id)
                                    ->where('member_id', $survivorMember->id)
                                    ->exists();

                                if ($exists) {
                                    DB::table('group_members')->where('id', $gm->id)->delete();
                                } else {
                                    DB::table('group_members')
                                        ->where('id', $gm->id)
                                        ->update(['member_id' => $survivorMember->id]);
                                }
                            }

                            // Delete duplicate member
                            DB::table('classroom_members')->where('id', $dupMember->id)->delete();
                        } else {
                            // Update classroom_id directly
                            DB::table('classroom_members')
                                ->where('id', $dupMember->id)
                                ->update(['classroom_id' => $survivor->id]);
                        }
                    }

                    // Step B: Merge classroom_students (classroom_id/student_id unique)
                    $dupStudents = DB::table('classroom_students')
                        ->where('classroom_id', $dupRoom->id)
                        ->get();

                    foreach ($dupStudents as $dupStudent) {
                        $survivorStudent = DB::table('classroom_students')
                            ->where('classroom_id', $survivor->id)
                            ->where('student_id', $dupStudent->student_id)
                            ->first();

                        if ($survivorStudent) {
                            // Keep the survivor one (delete the duplicate)
                            DB::table('classroom_students')->where('id', $dupStudent->id)->delete();
                        } else {
                            // Update classroom_id directly
                            DB::table('classroom_students')
                                ->where('id', $dupStudent->id)
                                ->update(['classroom_id' => $survivor->id]);
                        }
                    }

                    // Step C: Update other tables referencing classrooms.id
                    foreach ($foreignKeys as $fk) {
                        if (in_array($fk->TABLE_NAME, ['classroom_members', 'classroom_students'])) {
                            continue; // Already handled above
                        }

                        $rows = DB::table($fk->TABLE_NAME)
                            ->where($fk->COLUMN_NAME, $dupRoom->id)
                            ->get();

                        foreach ($rows as $row) {
                            try {
                                DB::table($fk->TABLE_NAME)
                                    ->where('id', $row->id)
                                    ->update([$fk->COLUMN_NAME => $survivor->id]);
                            } catch (QueryException $e) {
                                if ($e->getCode() === '23000') {
                                    // Unique index collision, delete duplicate
                                    DB::table($fk->TABLE_NAME)->where('id', $row->id)->delete();
                                } else {
                                    throw $e;
                                }
                            }
                        }
                    }

                    // Step D: Delete the duplicate classroom
                    DB::table('classrooms')->where('id', $dupRoom->id)->delete();
                    Log::info("Merged duplicate classroom ID {$dupRoom->id} into Survivor ID {$survivor->id}");
                });

                $this->info("  -> Classroom ID {$dupRoom->id} merged successfully.");
            }
        }

        $this->info("\nDone!");

        return 0;
    }
}
