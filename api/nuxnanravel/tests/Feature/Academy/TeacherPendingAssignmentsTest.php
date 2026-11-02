<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\AcademyMember;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/academies/{academy}/analytics/teacher-pending-assignments
 * GET /api/academies/{academy}/analytics/dashboard-stats (pending_grading)
 *
 * assignments ไม่มีคอลัมน์ course_id — ผูกคอร์สผ่าน assignmentable (morphTo)
 * ซึ่งปลายทางเป็น Course / Lesson / Topic เดิมทั้งสอง endpoint กรองแค่ Lesson::class
 * แต่งานผูกกับ Course ตรง ๆ เป็นหลัก (DB dev: 26/26 แถว) ผลจึงว่างเปล่าเสมอ
 * และ pending_grading เป็น 0 ทั้งที่มีงานรอตรวจหลายพันชิ้น
 *
 * เทสต์ชุดนี้ต้องยิง endpoint จริงเสมอ ไม่ใช่เรียก scope ตรง ๆ
 * เพราะบั๊กประเภทนี้จะโผล่ก็ต่อเมื่อ query ถูกรันจริงเท่านั้น
 */
class TeacherPendingAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private function url(Academy $academy): string
    {
        return "/api/academies/{$academy->id}/analytics/teacher-pending-assignments";
    }

    private function approveMember(Academy $academy, User $user, string $role = 'teacher'): void
    {
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
    }

    private function submitAnswer(Assignment $assignment, string $status = 'submitted', ?int $points = null): AssignmentAnswer
    {
        // assignment_id ไม่อยู่ใน $fillable ของ AssignmentAnswer — ต้องสร้างผ่าน relation
        return $assignment->answers()->create([
            'user_id' => User::factory()->create()->id,
            'content' => 'answer',
            'status' => $status,
            'points' => $points,
        ]);
    }

    private function courseAssignment(Course $course, array $attributes = []): Assignment
    {
        return Assignment::factory()->create(array_merge([
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
        ], $attributes));
    }

    public function test_owner_gets_pending_assignments_with_expected_shape(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'academy_id' => $academy->id,
            'name' => 'วิทยาการคำนวณ ม.4',
        ]);

        $assignment = $this->courseAssignment($course, ['title' => 'ใบงานที่ 1']);

        $this->submitAnswer($assignment);
        $this->submitAnswer($assignment);

        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    ['id', 'title', 'course', 'pending'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.title', 'ใบงานที่ 1')
            ->assertJsonPath('data.0.course', 'วิทยาการคำนวณ ม.4')
            ->assertJsonPath('data.0.pending', 2);
    }

    public function test_lesson_backed_assignments_are_included(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'name' => 'ภาษาไทย ม.1',
        ]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Lesson::class,
            'assignmentable_id' => $lesson->id,
        ]);
        $this->submitAnswer($assignment);

        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.course', 'ภาษาไทย ม.1');
    }

    /**
     * Topic เดินไปคอร์สผ่าน lesson ให้ตรงกับ Assignment::getLesson()
     */
    public function test_topic_backed_assignments_are_included(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'name' => 'คณิตศาสตร์ ม.2',
        ]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'user_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Topic::class,
            'assignmentable_id' => $topic->id,
        ]);
        $this->submitAnswer($assignment);

        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.course', 'คณิตศาสตร์ ม.2');
    }

    public function test_assignments_from_other_academies_are_excluded(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $otherAcademy = Academy::factory()->create();

        $foreignCourse = Course::factory()->create(['academy_id' => $otherAcademy->id]);
        $this->submitAnswer($this->courseAssignment($foreignCourse));

        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_assignments_without_pending_answers_are_excluded(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
        ]);

        // ตรวจแล้ว ให้คะแนนแล้ว = ไม่ค้าง
        $this->submitAnswer($this->courseAssignment($course), 'graded', 8);

        // ยังไม่มีใครส่ง = ไม่ค้าง
        $this->courseAssignment($course);

        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * ครูที่ไม่ใช่ผู้ดูแลโรงเรียนต้องเห็นเฉพาะงานในคอร์สที่ตัวเองดูแล
     */
    public function test_teacher_sees_only_assignments_from_courses_they_administer(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $teacher = User::factory()->create();
        $this->approveMember($academy, $teacher);

        $ownCourse = Course::factory()->create([
            'user_id' => $teacher->id,
            'instructor_id' => $teacher->id,
            'academy_id' => $academy->id,
            'name' => 'คอร์สของครูคนนี้',
        ]);
        $mine = $this->courseAssignment($ownCourse);
        $this->submitAnswer($mine);

        $colleagueCourse = Course::factory()->create([
            'academy_id' => $academy->id,
            'name' => 'คอร์สของครูคนอื่น',
        ]);
        $this->submitAnswer($this->courseAssignment($colleagueCourse));

        $this->actingAs($teacher, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.course', 'คอร์สของครูคนนี้');

        // เจ้าของโรงเรียนยังเห็นทั้งสองคอร์ส
        $this->actingAs($owner, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * ผู้ดูแลคอร์ส (course_members role 4) ต้องเห็นงานของคอร์สนั้นด้วย
     * ให้ผลตรงกับ Course::scopeAdministeredBy()
     */
    public function test_course_admin_member_sees_that_courses_assignments(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $teacher = User::factory()->create();
        $this->approveMember($academy, $teacher);

        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'name' => 'คอร์สที่ได้รับมอบหมาย',
        ]);
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'role' => 4,
            'status' => 1,
        ]);

        $assignment = $this->courseAssignment($course);
        $this->submitAnswer($assignment);

        $this->actingAs($teacher, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id);
    }

    /**
     * ครูที่เป็นแค่ courses.instructor_id (ไม่ใช่เจ้าของ ไม่ใช่ role 4) ต้องเห็นงานด้วย
     * AnalyticsTeacherPendingTest ครอบกรณีนี้ไว้เฉพาะงานที่ผูก Lesson — ตัวนี้ครอบงานที่ผูก Course
     */
    public function test_instructor_only_teacher_sees_course_backed_assignments(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $instructor = User::factory()->create();
        $this->approveMember($academy, $instructor);

        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $instructor->id,
            'academy_id' => $academy->id,
        ]);
        $assignment = $this->courseAssignment($course);
        $this->submitAnswer($assignment);

        $this->actingAs($instructor, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id);
    }

    /**
     * academy admin ที่ไม่ใช่เจ้าของต้องเห็นทุกคอร์สเหมือนเจ้าของ
     * เดิมเช็กแค่ academies.user_id จึงถูกนับเป็นครูธรรมดา
     */
    public function test_academy_admin_sees_every_course(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $admin = User::factory()->create();
        $this->approveMember($academy, $admin, 'admin');
        AcademyAdmin::create(['academy_id' => $academy->id, 'user_id' => $admin->id]);

        foreach (['คอร์ส ก', 'คอร์ส ข'] as $name) {
            $course = Course::factory()->create([
                'academy_id' => $academy->id,
                'name' => $name,
            ]);
            $this->submitAnswer($this->courseAssignment($course));
        }

        $this->actingAs($admin, 'api')->getJson($this->url($academy))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_dashboard_pending_grading_counts_every_morph_type(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
        ]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);

        // ผูก Course ตรง 2 ชิ้น + Lesson 1 ชิ้น — เดิมนับได้แค่ชิ้นของ Lesson
        $courseAssignment = $this->courseAssignment($course);
        $this->submitAnswer($courseAssignment);
        $this->submitAnswer($courseAssignment);
        $this->submitAnswer(Assignment::factory()->create([
            'assignmentable_type' => Lesson::class,
            'assignmentable_id' => $lesson->id,
        ]));

        // ของโรงเรียนอื่นต้องไม่ถูกนับ
        $foreign = Course::factory()->create(['academy_id' => Academy::factory()->create()->id]);
        $this->submitAnswer($this->courseAssignment($foreign));

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/analytics/dashboard-stats")
            ->assertStatus(200)
            ->assertJsonPath('data.pending_grading', 3);
    }

    public function test_non_member_is_rejected(): void
    {
        $academy = Academy::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')->getJson($this->url($academy))
            ->assertStatus(403);
    }
}
