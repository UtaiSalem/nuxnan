<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use App\Services\Import\QuestionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private function setUpQuizAndCourse()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);

        return [$user, $course, $quiz];
    }

    private function setUpLessonAndCourse()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        return [$user, $course, $lesson];
    }

    public function test_quiz_import_creates_questions_options_and_sets_correct_option_id()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $rows = [
            [
                'text' => 'Q1',
                'options' => ['A', 'B', 'C'],
                'correct' => 1, // 'B'
                'points' => 2,
                'pp_fine' => 0,
                'explanation' => 'Explain 1',
            ],
            [
                'text' => 'Q2',
                'options' => ['X', 'Y'],
                'correct' => 0, // 'X'
                'points' => 3,
                'pp_fine' => 1,
                'explanation' => 'Explain 2',
            ],
        ];

        $response = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import", [
            'rows' => $rows,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'imported' => 2]);

        $this->assertEquals(2, $quiz->questions()->count());

        $q1 = $quiz->questions()->where('text', 'Q1')->first();
        $this->assertNotNull($q1);
        $this->assertEquals(3, $q1->options()->count());
        $this->assertNotNull($q1->correct_option_id);
        $correctOption1 = $q1->options()->where('is_correct', true)->first();
        $this->assertEquals('B', $correctOption1->text);
        $this->assertEquals($correctOption1->id, $q1->correct_option_id);

        $q2 = $quiz->questions()->where('text', 'Q2')->first();
        $this->assertNotNull($q2);
        $this->assertEquals(2, $q2->options()->count());
        $this->assertNotNull($q2->correct_option_id);
        $correctOption2 = $q2->options()->where('is_correct', true)->first();
        $this->assertEquals('X', $correctOption2->text);
        $this->assertEquals($correctOption2->id, $q2->correct_option_id);
    }

    public function test_quiz_import_recomputes_quiz_counters()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $q = Question::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'questionable_type' => CourseQuiz::class,
            'questionable_id' => $quiz->id,
            'points' => 5,
            'text' => 'Dummy Q',
        ]);

        $rows = [
            ['text' => 'Q1', 'options' => ['A', 'B'], 'correct' => 0, 'points' => 2],
            ['text' => 'Q2', 'options' => ['C', 'D'], 'correct' => 1, 'points' => 3],
        ];

        $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import", [
            'rows' => $rows,
        ])->assertStatus(200);

        $quiz->refresh();
        $this->assertEquals(3, $quiz->total_questions);
        $this->assertEquals(10, $quiz->total_score); // 5 + 2 + 3
    }

    public function test_import_appends_and_never_deletes_existing_questions()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $existingQuestion = Question::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'questionable_type' => CourseQuiz::class,
            'questionable_id' => $quiz->id,
            'position' => 1,
            'text' => 'Old Q',
            'points' => 1,
        ]);

        $rows = [
            ['text' => 'New Q', 'options' => ['A', 'B'], 'correct' => 0, 'points' => 1],
        ];

        $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import", [
            'rows' => $rows,
        ])->assertStatus(200);

        $this->assertDatabaseHas('questions', ['id' => $existingQuestion->id]);
        $newQ = Question::where('text', 'New Q')->first();
        $this->assertEquals(2, $newQ->position);
    }

    public function test_preview_returns_row_errors_without_writing()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $csvContent = "\xEF\xBB\xBFคำถาม,ตัวเลือก1,ตัวเลือก2,เฉลย\nQ1,A,B,\nQ2,A,,1";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import/preview", [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('summary.invalid', 2);

        $this->assertEquals(0, $quiz->questions()->count());
    }

    public function test_import_rejects_non_manager()
    {
        [$owner, $course, $quiz] = $this->setUpQuizAndCourse();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import", [
            'rows' => [['text' => 'Q1', 'options' => ['A', 'B'], 'correct' => 0, 'points' => 1]],
        ])->assertStatus(403);
    }

    public function test_lesson_import_creates_questions()
    {
        [$user, $course, $lesson] = $this->setUpLessonAndCourse();

        $rows = [
            ['text' => 'Lesson Q', 'options' => ['A', 'B'], 'correct' => 0, 'points' => 1],
        ];

        $this->actingAs($user, 'api')->postJson("/api/lessons/{$lesson->id}/questions/import", [
            'rows' => $rows,
        ])->assertStatus(200);

        $this->assertEquals(1, $lesson->questions()->count());
        $q = $lesson->questions()->first();
        $this->assertEquals('Lesson Q', $q->text);
        $this->assertEquals(Lesson::class, $q->questionable_type);
    }

    public function test_template_endpoint_downloads_xlsx()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $response = $this->actingAs($user, 'api')->get("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import/template");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=question-import-template.xlsx');
    }

    public function test_build_template_does_not_leak_a_temp_file()
    {
        $service = app(QuestionImportService::class);
        $pattern = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qtp*';
        $before = glob($pattern);

        $path = $service->buildTemplate();
        @unlink($path);

        $after = glob($pattern);
        $this->assertEquals($before, $after);
    }

    public function test_preview_rejects_a_file_with_only_a_header_row()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $csvContent = "\xEF\xBB\xBFคำถาม,ตัวเลือก1,ตัวเลือก2,เฉลย\n";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import/preview", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $this->assertEquals('ไม่พบข้อมูลคำถามในไฟล์', $response->json('message'));
    }

    public function test_decimal_points_are_rejected_not_silently_truncated()
    {
        [$user, $course, $quiz] = $this->setUpQuizAndCourse();

        $csvContentError = "\xEF\xBB\xBFคำถาม,ตัวเลือก1,ตัวเลือก2,เฉลย,คะแนน\nQ1,A,B,1,2.7";
        $fileError = UploadedFile::fake()->createWithContent('test_error.csv', $csvContentError);

        $responseError = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import/preview", [
            'file' => $fileError,
        ]);

        $responseError->assertStatus(200);
        $responseError->assertJsonPath('summary.invalid', 1);
        $this->assertContains('คะแนนต้องเป็นจำนวนเต็ม', $responseError->json('rows.0.errors'));

        $csvContentOk = "\xEF\xBB\xBFคำถาม,ตัวเลือก1,ตัวเลือก2,เฉลย,คะแนน\nQ1,A,B,1,3";
        $fileOk = UploadedFile::fake()->createWithContent('test_ok.csv', $csvContentOk);

        $responseOk = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions/import/preview", [
            'file' => $fileOk,
        ]);

        $responseOk->assertStatus(200);
        $responseOk->assertJsonPath('summary.invalid', 0);
        $this->assertEmpty($responseOk->json('rows.0.errors'));
    }

    public function test_generated_template_round_trips_through_the_parser()
    {
        $service = app(QuestionImportService::class);
        $path = $service->buildTemplate();

        $file = new UploadedFile($path, 'template.xlsx', null, null, true);

        $parsed = $service->parse($file);
        $validated = $service->validateRows($parsed['rows']);

        $this->assertCount(2, $validated);
        foreach ($validated as $row) {
            $this->assertEmpty($row['errors']);
            $this->assertEmpty($row['warnings']);
        }

        @unlink($path);
    }
}
