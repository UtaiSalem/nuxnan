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
 * teacher-pending-assignments เคย 500 ทุกครั้ง — เรียก whereHas('course')/where('teacher_id')
 * ที่ไม่มีจริงบน Assignment (polymorphic ผ่าน assignmentable → Lesson)
 * แดชบอร์ดครูกลืน error ไว้ (การ์ด "งานรอตรวจ" เลยว่างเสมอ) และ useApi retry 500 ×3 = โหลดช้า
 */
class AnalyticsTeacherPendingTest extends TestCase
{
    use RefreshDatabase;

    private function member(Academy $academy, User $user): void
    {
        DB::table('academy_members')->insert([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'status' => 2,
            'role' => 'teacher',
            'enrollment_date' => '2026-05-16',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function pendingAnswer(int $assignmentId, int $userId, ?int $points, string $status): void
    {
        DB::table('assignment_answers')->insert([
            'assignment_id' => $assignmentId,
            'user_id' => $userId,
            'content' => 'x',
            'points' => $points,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setup2(): array
    {
        $owner = User::factory()->create(['username' => 'owner_u', 'email' => 'o@x.test']);
        $teacherA = User::factory()->create(['username' => 'tA', 'email' => 'ta@x.test']);
        $teacherB = User::factory()->create(['username' => 'tB', 'email' => 'tb@x.test']);
        $student = User::factory()->create(['username' => 'stu', 'email' => 's@x.test']);

        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1', 'display_name' => 'S1', 'description' => 'x',
        ]);
        $this->member($academy, $teacherA);
        $this->member($academy, $teacherB);

        // teacherA เป็นผู้สอนคอร์สนี้ (instructor_id)
        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $teacherA->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);
        $lesson = Lesson::create(['course_id' => $course->id, 'user_id' => $owner->id, 'title' => 'บทที่ 1', 'order' => 1, 'status' => 1]);

        $assignment = Assignment::create([
            'assignmentable_type' => Lesson::class, 'assignmentable_id' => $lesson->id,
            'title' => 'งานที่ 1', 'status' => 1, 'points' => 10,
        ]);
        // ค้างตรวจ 1 (points null) + ส่งแล้วยังไม่ให้คะแนน 1 (status submitted) + ตรวจแล้ว 1 (ต้องไม่ถูกนับ)
        $this->pendingAnswer($assignment->id, $student->id, null, 'submitted');
        $this->pendingAnswer($assignment->id, $owner->id, 8, 'graded');

        return compact('owner', 'teacherA', 'teacherB', 'academy', 'course', 'assignment');
    }

    public function test_owner_sees_pending_with_course_name_and_count()
    {
        $c = $this->setup2();

        $res = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/analytics/teacher-pending-assignments");

        $res->assertStatus(200);
        $res->assertJsonCount(1, 'data');
        $res->assertJsonPath('data.0.title', 'งานที่ 1');
        $res->assertJsonPath('data.0.course', 'คณิตศาสตร์');
        $res->assertJsonPath('data.0.pending', 1); // นับเฉพาะที่ยังไม่ตรวจ
    }

    public function test_instructor_sees_own_course_pending()
    {
        $c = $this->setup2();

        $res = $this->actingAs($c['teacherA'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/analytics/teacher-pending-assignments");

        $res->assertStatus(200);
        $res->assertJsonCount(1, 'data');
    }

    public function test_other_teacher_does_not_see_pending_of_course_they_do_not_teach()
    {
        $c = $this->setup2();

        $res = $this->actingAs($c['teacherB'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/analytics/teacher-pending-assignments");

        $res->assertStatus(200);
        $res->assertJsonCount(0, 'data');
    }
}
