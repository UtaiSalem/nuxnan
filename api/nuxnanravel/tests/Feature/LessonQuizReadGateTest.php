<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Topic;
use App\Models\TopicReadProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Owner policy: a lesson's quiz (questions attached to the lesson) may only be
 * viewed and answered after the student has read the lesson — always, regardless
 * of require_completion_before_exercises. Course membership is checked first.
 */
class LessonQuizReadGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $member;

    protected User $outsider;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->member = User::factory()->create();
        $this->outsider = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->teacher->id]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'course_member_status' => 1,
            'role' => 4,
            'status' => 1,
        ]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->member->id,
            'course_member_status' => 1,
        ]);
    }

    /**
     * @return array{0: Lesson, 1: Question, 2: QuestionOption}
     */
    private function makeLessonWithQuestion(int $minRead = 0): array
    {
        $lesson = Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => 'Lesson',
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'min_read' => $minRead,
            'require_completion_before_exercises' => false,
        ]);

        $question = $lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Q',
            'points' => 10,
        ]);

        $option = QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => 'Right',
            'is_correct' => true,
        ]);

        return [$lesson, $question, $option];
    }

    private function makeTopics(Lesson $lesson, int $count)
    {
        return Topic::factory()->count($count)->create([
            'course_id' => $this->course->id,
            'lesson_id' => $lesson->id,
            'status' => 'published',
            'min_read' => 1,
        ]);
    }

    private function markTopicRead(Lesson $lesson, Topic $topic, User $user): void
    {
        TopicReadProgress::create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
            'lesson_id' => $lesson->id,
            'topic_id' => $topic->id,
            'status' => TopicReadProgress::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'required_seconds_snapshot' => 60,
            'elapsed_seconds' => 300,
        ]);
    }

    private function setTimeSpent(Lesson $lesson, User $user, int $seconds, string $status = 'in_progress'): void
    {
        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'status' => $status,
                'started_at' => now()->subMinutes(5),
                'time_spent_seconds' => $seconds,
            ]
        );
    }

    private function getQuestions(User $user, Lesson $lesson)
    {
        return $this->actingAs($user, 'api')->getJson("/api/lessons/{$lesson->id}/questions");
    }

    private function answer(User $user, Lesson $lesson, Question $question, QuestionOption $option)
    {
        return $this->actingAs($user, 'api')
            ->postJson("/api/lessons/{$lesson->id}/questions/{$question->id}/answer", [
                'answer_id' => $option->id,
            ]);
    }

    private function showLesson(User $user, Lesson $lesson)
    {
        return $this->actingAs($user, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$lesson->id}");
    }

    public function test_partially_read_topics_block_quiz_view_answer_and_resource(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion();
        $topics = $this->makeTopics($lesson, 2);
        $this->markTopicRead($lesson, $topics[0], $this->member);

        $this->getQuestions($this->member, $lesson)
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'LESSON_READ_REQUIRED']);

        $this->answer($this->member, $lesson, $question, $option)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'LESSON_READ_REQUIRED']);

        $this->assertDatabaseCount('lesson_answer_questions', 0);

        $this->showLesson($this->member, $lesson)
            ->assertOk()
            ->assertJsonPath('lesson.can_answer_questions', true)
            ->assertJsonPath('lesson.can_take_quiz', false)
            ->assertJsonPath('lesson.questions', []);
    }

    public function test_reveal_is_blocked_until_lesson_is_read(): void
    {
        [$lesson, $question] = $this->makeLessonWithQuestion();
        $this->makeTopics($lesson, 2);

        $this->actingAs($this->member, 'api')
            ->postJson("/api/lessons/{$lesson->id}/questions/{$question->id}/reveal")
            ->assertStatus(422)
            ->assertJson(['code' => 'LESSON_READ_REQUIRED']);
    }

    public function test_all_topics_read_allows_quiz(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion();
        foreach ($this->makeTopics($lesson, 2) as $topic) {
            $this->markTopicRead($lesson, $topic, $this->member);
        }

        $this->getQuestions($this->member, $lesson)->assertOk()->assertJsonCount(1, 'data');

        $this->answer($this->member, $lesson, $question, $option)
            ->assertOk()
            ->assertJson(['success' => true, 'is_correct' => true]);

        $this->showLesson($this->member, $lesson)
            ->assertOk()
            ->assertJsonPath('lesson.can_take_quiz', true)
            ->assertJsonCount(1, 'lesson.questions');
    }

    public function test_lesson_without_topics_and_min_read_uses_60_second_fallback(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion(0);

        $this->assertSame(Lesson::QUIZ_FALLBACK_READ_SECONDS, $lesson->getRequiredReadSeconds());

        $this->setTimeSpent($lesson, $this->member, 0);

        $this->getQuestions($this->member, $lesson)
            ->assertStatus(403)
            ->assertJson(['code' => 'LESSON_READ_REQUIRED']);
        $this->answer($this->member, $lesson, $question, $option)
            ->assertStatus(422)
            ->assertJson(['code' => 'LESSON_READ_REQUIRED']);

        $this->setTimeSpent($lesson, $this->member, 59);
        $this->getQuestions($this->member, $lesson)
            ->assertStatus(403)
            ->assertJson(['code' => 'LESSON_READ_REQUIRED']);

        $this->setTimeSpent($lesson, $this->member, 60);

        $this->getQuestions($this->member, $lesson)->assertOk();
        $this->answer($this->member, $lesson, $question, $option)
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_null_min_read_uses_fallback_and_guest_cannot_access_quiz(): void
    {
        [$lesson] = $this->makeLessonWithQuestion();
        $lesson->min_read = null;

        $this->assertSame(60, $lesson->getRequiredReadSeconds());
        $this->assertFalse($lesson->canAccessQuizBy(null));
        $this->assertFalse($lesson->canAccessQuizBy($this->member));
        $this->setTimeSpent($lesson, $this->member, 60);
        $this->assertTrue($lesson->canAccessQuizBy($this->member));
    }

    public function test_completed_lesson_allows_quiz(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion();
        $this->makeTopics($lesson, 2);

        // Completed earlier (e.g. before topics were added): no topic reads, no time.
        $this->setTimeSpent($lesson, $this->member, 0, LessonProgress::STATUS_COMPLETED);

        $this->getQuestions($this->member, $lesson)->assertOk();
        $this->answer($this->member, $lesson, $question, $option)->assertOk();
        $this->showLesson($this->member, $lesson)
            ->assertOk()
            ->assertJsonPath('lesson.can_take_quiz', true);
    }

    public function test_course_admin_is_always_allowed(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion(30);
        $this->makeTopics($lesson, 2);

        $this->getQuestions($this->teacher, $lesson)->assertOk();
        $this->answer($this->teacher, $lesson, $question, $option)->assertOk();
        $this->showLesson($this->teacher, $lesson)
            ->assertOk()
            ->assertJsonPath('lesson.can_take_quiz', true)
            ->assertJsonCount(1, 'lesson.questions');
    }

    public function test_non_member_still_gets_not_course_member_first(): void
    {
        [$lesson, $question, $option] = $this->makeLessonWithQuestion();

        $this->answer($this->outsider, $lesson, $question, $option)
            ->assertStatus(403)
            ->assertJson(['code' => 'NOT_COURSE_MEMBER']);

        $this->actingAs($this->outsider, 'api')
            ->postJson("/api/lessons/{$lesson->id}/questions/{$question->id}/reveal")
            ->assertStatus(403)
            ->assertJson(['code' => 'NOT_COURSE_MEMBER']);

        $view = $this->getQuestions($this->outsider, $lesson)->assertStatus(403);
        $this->assertNotSame('LESSON_READ_REQUIRED', $view->json('code'));
    }
}
