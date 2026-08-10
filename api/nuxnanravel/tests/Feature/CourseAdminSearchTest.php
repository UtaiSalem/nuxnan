<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * course_members.role is a tinyint (1=student, 2=student_leader, 3=teacher,
 * 4=admin). The search endpoints used to filter it with role *names*, so a
 * co-admin matched nothing and only course owners ever saw results.
 */
class CourseAdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeCoAdmin(Course $course): User
    {
        $admin = User::factory()->create();

        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'role' => 4,
            'status' => 1,
        ]);

        return $admin;
    }

    public function test_a_co_admin_finds_courses_they_administer()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id, 'name' => 'วิทยาการคำนวณ']);
        $admin = $this->makeCoAdmin($course);

        $response = $this->actingAs($admin, 'api')->getJson('/api/courses/search?q=วิทยาการ');

        $response->assertOk();
        $this->assertSame([$course->id], collect($response->json('courses'))->pluck('id')->all());
    }

    public function test_a_co_admin_finds_quizzes_they_can_duplicate()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $quiz = CourseQuiz::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
            'title' => 'แบบทดสอบกลางภาค',
        ]);
        $admin = $this->makeCoAdmin($course);

        $response = $this->actingAs($admin, 'api')->getJson('/api/quizzes/search?q=กลางภาค');

        $response->assertOk();
        $this->assertSame([$quiz->id], collect($response->json('quizzes'))->pluck('id')->all());
    }

    public function test_search_still_hides_courses_the_user_only_studies()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id, 'name' => 'วิทยาการคำนวณ']);

        $student = User::factory()->create();
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 1,
            'status' => 1,
        ]);

        $response = $this->actingAs($student, 'api')->getJson('/api/courses/search?q=วิทยาการ');

        $response->assertOk();
        $this->assertSame([], $response->json('courses'));
    }

    public function test_search_hides_courses_where_the_admin_row_is_inactive()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id, 'name' => 'วิทยาการคำนวณ']);

        $pending = User::factory()->create();
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $pending->id,
            'role' => 4,
            'status' => 0,
        ]);

        $response = $this->actingAs($pending, 'api')->getJson('/api/courses/search?q=วิทยาการ');

        $response->assertOk();
        $this->assertSame([], $response->json('courses'));
    }

    public function test_a_co_admin_can_duplicate_a_quiz_they_found()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id, 'user_id' => $owner->id]);
        $admin = $this->makeCoAdmin($course);

        $target = Course::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin, 'api')->postJson("/api/quizzes/{$quiz->id}/duplicate", [
            'course_id' => $target->id,
        ])->assertOk();

        $this->assertSame(1, CourseQuiz::where('course_id', $target->id)->count());
    }
}
