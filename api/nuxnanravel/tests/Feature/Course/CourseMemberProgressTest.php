<?php

namespace Tests\Feature\Course;

use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseMemberProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_progress_endpoint_structure_and_query_count()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);

        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'title' => 'Test Lesson',
            'order' => 1,
        ]);

        $topic = Topic::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Test Topic',
        ]);

        $assignment = Assignment::factory()->create([
            'assignmentable_id' => $topic->id,
            'assignmentable_type' => Topic::class,
            'status' => 1,
            'points' => 10,
        ]);

        // Attempt assignment (awaiting grading)
        AssignmentAnswer::forceCreate([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);

        // Check as student
        $this->actingAs($student, 'api');

        DB::enableQueryLog();

        $response = $this->getJson("/api/courses/{$course->id}/members/{$member->id}/progress");

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThan(60, count($queries), 'N+1 query detected in member progress.');

        $response->assertJsonStructure([
            'lessons' => [
                '*' => [
                    'id',
                    'title',
                    'has_graded_activity',
                    'score_status',
                    'score',
                    'max_score',
                    'activity_counts',
                ],
            ],
        ]);

        $lessonsData = $response->json('lessons');
        $this->assertCount(1, $lessonsData);
        $this->assertEquals('submitted', $lessonsData[0]['score_status']);
    }
}
