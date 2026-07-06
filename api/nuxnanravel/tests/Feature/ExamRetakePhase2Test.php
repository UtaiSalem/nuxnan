<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\CourseRemediationSession;
use App\Models\User;
use App\Services\RemediationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamRetakePhase2Test extends TestCase
{
    use RefreshDatabase;

    protected RemediationService $remediationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remediationService = app(RemediationService::class);
    }

    public function test_pass_remediation_grants_quiz_retake()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 'student',
            'completion_status' => 'failed',
            'draft_grade' => 'F',
        ]);

        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);

        $session = CourseRemediationSession::create([
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'title' => 'Retake Session',
            'start_at' => now(),
            'end_at' => now()->addHour(),
            'type' => 'exam_retake',
            'status' => 'open',
            'passing_score' => 60,
            'created_by' => $teacher->id,
        ]);

        $enrollment = $this->remediationService->enrollStudent($session, $member);

        // Pass the remediation
        $this->remediationService->gradeEnrollment($enrollment, 80, $teacher, 'Passed well');

        // Check if retake is granted
        $quizResult = CourseQuizResult::where('user_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        $this->assertNotNull($quizResult);
        $this->assertNotNull($quizResult->retake_unlocked_at);
        $this->assertNull($quizResult->retake_used_at);
        $this->assertEquals($enrollment->id, $quizResult->retake_granted_by_enrollment_id);
    }

    public function test_starting_quiz_marks_retake_as_used()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);

        // Manually grant a retake
        $quizResult = CourseQuizResult::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'retake_unlocked_at' => now(),
            'status' => 0,
        ]);

        $this->actingAs($student, 'api');

        // Start the quiz via API
        $response = $this->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/results");

        $response->assertStatus(201);

        $quizResult->refresh();
        $this->assertNotNull($quizResult->retake_used_at);
    }

    public function test_fail_remediation_does_not_grant_retake()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 'student',
            'completion_status' => 'failed',
            'draft_grade' => 'F',
        ]);

        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);

        $session = CourseRemediationSession::create([
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'title' => 'Retake Session',
            'start_at' => now(),
            'end_at' => now()->addHour(),
            'type' => 'exam_retake',
            'status' => 'open',
            'passing_score' => 60,
            'created_by' => $teacher->id,
        ]);

        $enrollment = $this->remediationService->enrollStudent($session, $member);

        // Fail the remediation
        $this->remediationService->gradeEnrollment($enrollment, 30, $teacher, 'Failed');

        // Check if retake is NOT granted
        $quizResult = CourseQuizResult::where('user_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        $this->assertNull($quizResult?->retake_unlocked_at);
    }
}
