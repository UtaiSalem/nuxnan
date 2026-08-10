<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers uploading pictures while editing an existing question or option —
 * the flow used by the quiz editor page.
 */
class QuestionImageEditTest extends TestCase
{
    use RefreshDatabase;

    private const QUIZ_IMAGE_PATH = 'images/courses/quizzes/questions';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return array{0: User, 1: Course, 2: CourseQuiz, 3: Question}
     */
    private function setUpQuestion(): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id, 'user_id' => $user->id]);

        $question = Question::create([
            'questionable_type' => 'App\Models\CourseQuiz',
            'questionable_id' => $quiz->id,
            'course_id' => $course->id,
            'user_id' => $user->id,
            'text' => 'คำถามเดิม',
            'points' => 1,
        ]);

        return [$user, $course, $quiz, $question];
    }

    public function test_uploading_an_image_while_editing_a_question_saves_it()
    {
        [$user, $course, $quiz, $question] = $this->setUpQuestion();

        $response = $this->actingAs($user, 'api')->post(
            "/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/{$question->id}",
            [
                '_method' => 'PATCH',
                'text' => 'คำถามที่แก้ไขแล้ว',
                'points' => 1,
                'images' => [UploadedFile::fake()->image('diagram.png')],
            ]
        );

        $response->assertOk();

        $images = $question->fresh()->images;
        $this->assertCount(1, $images, 'Editing a question did not persist the uploaded image');
        Storage::disk('public')->assertExists(self::QUIZ_IMAGE_PATH.'/'.$images->first()->filename);

        // And it must resolve to a real URL rather than the not-found fallback.
        $this->assertStringContainsString(
            'storage/'.self::QUIZ_IMAGE_PATH.'/'.$images->first()->filename,
            $images->first()->url
        );
    }

    public function test_re_uploading_replaces_the_previous_question_image()
    {
        [$user, $course, $quiz, $question] = $this->setUpQuestion();

        $oldFilename = 'old_'.uniqid().'.png';
        Storage::disk('public')->put(self::QUIZ_IMAGE_PATH.'/'.$oldFilename, 'old');
        $question->images()->create(['filename' => $oldFilename]);

        $this->actingAs($user, 'api')->post(
            "/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/{$question->id}",
            [
                '_method' => 'PATCH',
                'text' => 'คำถามเดิม',
                'points' => 1,
                'images' => [UploadedFile::fake()->image('new.png')],
            ]
        )->assertOk();

        $images = $question->fresh()->images;

        // The editor shows one picture per question — the new upload takes over.
        $this->assertCount(1, $images);
        $this->assertNotSame($oldFilename, $images->first()->filename);
        Storage::disk('public')->assertMissing(self::QUIZ_IMAGE_PATH.'/'.$oldFilename);
        Storage::disk('public')->assertExists(self::QUIZ_IMAGE_PATH.'/'.$images->first()->filename);
    }

    public function test_editing_a_question_without_an_upload_keeps_its_image()
    {
        [$user, $course, $quiz, $question] = $this->setUpQuestion();

        $filename = 'keep_'.uniqid().'.png';
        Storage::disk('public')->put(self::QUIZ_IMAGE_PATH.'/'.$filename, 'keep');
        $question->images()->create(['filename' => $filename]);

        $this->actingAs($user, 'api')->patchJson(
            "/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/{$question->id}",
            ['text' => 'แก้เฉพาะข้อความ', 'points' => 2]
        )->assertOk();

        $this->assertSame($filename, $question->fresh()->images->first()->filename);
        Storage::disk('public')->assertExists(self::QUIZ_IMAGE_PATH.'/'.$filename);
    }

    public function test_uploading_an_image_while_editing_an_option_saves_it()
    {
        [$user, , , $question] = $this->setUpQuestion();

        $option = QuestionOption::create([
            'optionable_type' => 'App\Models\Question',
            'optionable_id' => $question->id,
            'text' => '',
            'is_correct' => 1,
            'position' => 1,
        ]);

        $response = $this->actingAs($user, 'api')->post(
            "/api/questions/{$question->id}/options/{$option->id}",
            [
                '_method' => 'PATCH',
                'text' => '',
                'is_correct' => 1,
                'images' => [UploadedFile::fake()->image('choice.png')],
            ]
        );

        $response->assertOk();

        $images = $option->fresh()->images;
        $this->assertCount(1, $images, 'Editing an option did not persist the uploaded image');
        Storage::disk('public')->assertExists(self::QUIZ_IMAGE_PATH.'/'.$images->first()->filename);
    }

    public function test_re_uploading_replaces_the_previous_option_image()
    {
        [$user, , , $question] = $this->setUpQuestion();

        $option = QuestionOption::create([
            'optionable_type' => 'App\Models\Question',
            'optionable_id' => $question->id,
            'text' => '',
            'is_correct' => 1,
            'position' => 1,
        ]);

        $oldFilename = 'old_opt_'.uniqid().'.png';
        Storage::disk('public')->put(self::QUIZ_IMAGE_PATH.'/'.$oldFilename, 'old');
        $option->images()->create(['filename' => $oldFilename]);

        $this->actingAs($user, 'api')->post(
            "/api/questions/{$question->id}/options/{$option->id}",
            [
                '_method' => 'PATCH',
                'is_correct' => 1,
                'images' => [UploadedFile::fake()->image('new_choice.png')],
            ]
        )->assertOk();

        $images = $option->fresh()->images;

        $this->assertCount(1, $images);
        $this->assertNotSame($oldFilename, $images->first()->filename);
        Storage::disk('public')->assertMissing(self::QUIZ_IMAGE_PATH.'/'.$oldFilename);
    }

    public function test_creating_a_question_with_an_image_still_works()
    {
        [$user, $course, $quiz] = $this->setUpQuestion();

        $response = $this->actingAs($user, 'api')->post(
            "/api/courses/{$course->id}/quizzes/{$quiz->id}/questions",
            [
                'text' => 'คำถามใหม่',
                'points' => 1,
                'images' => [UploadedFile::fake()->image('fresh.png')],
            ]
        );

        $response->assertOk();

        $created = Question::where('questionable_id', $quiz->id)->where('text', 'คำถามใหม่')->firstOrFail();
        $this->assertCount(1, $created->images);
        Storage::disk('public')->assertExists(self::QUIZ_IMAGE_PATH.'/'.$created->images->first()->filename);
    }
}
