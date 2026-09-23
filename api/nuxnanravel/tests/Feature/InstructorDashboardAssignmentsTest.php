<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * InstructorDashboardController เคยผูกกับตารางที่ไม่มีจริง (course_assignments/course_assignment_files ฯลฯ)
 * รอบนี้ผูกกับ schema จริง: Assignment (polymorphic → Course/Lesson) + assignment_answers
 */
class InstructorDashboardAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private function member(Course $course, User $user): void
    {
        DB::table('course_members')->insert([
            'course_id' => $course->id, 'user_id' => $user->id,
            'course_member_status' => 1, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function answer(int $assignmentId, int $userId, ?int $points): void
    {
        DB::table('assignment_answers')->insert([
            'assignment_id' => $assignmentId, 'user_id' => $userId,
            'content' => 'x', 'points' => $points,
            'status' => $points === null ? 'submitted' : 'graded',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_course_dashboard_binds_to_real_assignments()
    {
        $owner = User::factory()->create(['username' => 'owner_u', 'email' => 'o@x.test']);
        $s1 = User::factory()->create(['username' => 's1', 'email' => 's1@x.test']);
        $s2 = User::factory()->create(['username' => 's2', 'email' => 's2@x.test']);

        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1', 'display_name' => 'S1', 'description' => 'x',
        ]);
        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คอร์สทดสอบ', 'code' => 'C1',
        ]);
        $this->member($course, $s1);
        $this->member($course, $s2);

        // งานแนบกับบทเรียน (assignmentable = Lesson) — เส้นทางที่ระบบใช้จริง
        $lesson = Lesson::create(['course_id' => $course->id, 'user_id' => $owner->id, 'title' => 'บทที่ 1', 'order' => 1, 'status' => 1]);
        $lessonAssignment = Assignment::create([
            'assignmentable_type' => Lesson::class, 'assignmentable_id' => $lesson->id,
            'title' => 'งานบทเรียน', 'status' => 1, 'points' => 10,
        ]);
        // งานแนบกับคอร์สโดยตรง (assignmentable = Course)
        $courseAssignment = Assignment::create([
            'assignmentable_type' => Course::class, 'assignmentable_id' => $course->id,
            'title' => 'งานคอร์ส', 'status' => 1, 'points' => 20,
        ]);

        // คำตอบ: ตรวจแล้ว 2 (points) + ค้างตรวจ 1 (null)
        $this->answer($lessonAssignment->id, $s1->id, 8);
        $this->answer($lessonAssignment->id, $s2->id, null);
        $this->answer($courseAssignment->id, $s1->id, 18);

        $res = $this->actingAs($owner, 'api')
            ->getJson("/api/courses/{$course->id}/instructor-dashboard");

        $res->assertStatus(200);
        // งานรวม = คอร์ส 1 + บทเรียน 1 = 2 (ต้องตรงกันทั้ง overview และ assignments.total)
        $res->assertJsonPath('data.overview.total_assignments', 2);
        $res->assertJsonPath('data.assignments.total', 2);
        $res->assertJsonPath('data.assignments.total_submissions', 3);
        $res->assertJsonPath('data.assignments.graded_count', 2);
        $res->assertJsonPath('data.assignments.pending_count', 1);
        $res->assertJsonPath('data.pending_items.pending_assignment_grading', 1);
    }

    public function test_performance_trends_and_at_risk_do_not_500()
    {
        $owner = User::factory()->create(['username' => 'owner_u', 'email' => 'o@x.test']);
        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1', 'display_name' => 'S1', 'description' => 'x',
        ]);
        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คอร์สทดสอบ', 'code' => 'C1',
        ]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/courses/{$course->id}/instructor-dashboard/trends")
            ->assertStatus(200);
        $this->actingAs($owner, 'api')
            ->getJson("/api/courses/{$course->id}/instructor-dashboard/at-risk")
            ->assertStatus(200);
    }
}
