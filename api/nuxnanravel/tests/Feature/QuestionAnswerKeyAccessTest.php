<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/questions/{question}/answers returns the answer key.
 * Only the question's author and course admins may read it.
 */
class QuestionAnswerKeyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected User $outsider;

    protected Question $question;

    protected QuestionOption $correctOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->outsider = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $this->teacher->id]);

        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 'active',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'user_id' => $this->teacher->id,
            'title' => 'Lesson',
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'require_completion_before_exercises' => false,
        ]);

        $this->question = $lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $course->id,
            'text' => 'Q',
            'points' => 10,
        ]);

        $this->correctOption = QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $this->question->id,
            'text' => 'Right',
            'is_correct' => true,
        ]);

        $this->question->update(['correct_option_id' => $this->correctOption->id]);
    }

    private function url(): string
    {
        return "/api/questions/{$this->question->id}/answers";
    }

    public function test_enrolled_student_cannot_read_answer_key(): void
    {
        $this->actingAs($this->student, 'api')
            ->getJson($this->url())
            ->assertStatus(403)
            ->assertJsonMissing(['answer' => $this->correctOption->id]);
    }

    public function test_non_member_cannot_read_answer_key(): void
    {
        $this->actingAs($this->outsider, 'api')
            ->getJson($this->url())
            ->assertStatus(403);
    }

    public function test_guest_cannot_read_answer_key(): void
    {
        $this->getJson($this->url())->assertStatus(401);
    }

    public function test_question_author_can_read_answer_key(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->getJson($this->url())
            ->assertOk()
            ->assertJson(['answer' => $this->correctOption->id]);
    }
}
