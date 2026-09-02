<?php

namespace Tests\Feature\Course;

use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentGetLessonNullSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_lesson_returns_null_when_topic_is_missing()
    {
        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Topic::class,
            'assignmentable_id' => 99999, // Non-existent topic
        ]);

        $lesson = $assignment->getLesson();

        $this->assertNull($lesson);
    }

    public function test_answers_endpoint_does_not_500_when_topic_is_missing()
    {
        $student = User::factory()->create();

        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Topic::class,
            'assignmentable_id' => 99999, // Non-existent topic
            'status' => 1, // is_published
            'points' => 10,
        ]);

        AssignmentAnswer::forceCreate([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($student, 'api');

        $response = $this->getJson("/api/assignments/{$assignment->id}/answers");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_get_lesson_still_resolves_through_an_existing_topic()
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id]);

        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Topic::class,
            'assignmentable_id' => $topic->id,
        ]);

        $resolvedLesson = $assignment->getLesson();

        $this->assertNotNull($resolvedLesson);
        $this->assertEquals($lesson->id, $resolvedLesson->id);
    }
}
