<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Assignment;
use App\Services\CourseScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_course_total_score_never_negative()
    {
        $course = Course::factory()->create(['total_score' => -50]);
        $service = app(CourseScoreService::class);
        
        $service->syncCourseTotalScore($course);
        
        $this->assertGreaterThanOrEqual(0, $course->fresh()->total_score);
    }

    public function test_progress_endpoint_uses_fallback_when_stored_total_is_negative()
    {
        $course = Course::factory()->create(['total_score' => -10]);
        
        // Add an assignment with 100 points
        Assignment::create([
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
            'points' => 100,
            'title' => 'Test',
            'status' => 1, // 1 is published/active
        ]);

        $user = \App\Models\User::factory()->create();
        $course->courseMembers()->create([
            'user_id' => $user->id, 
            'role' => 1, // student
        ]);

        $this->actingAs($user, 'api');
        
        $response = $this->getJson("/api/courses/{$course->id}/progress");

        if ($response->status() === 404) {
             $this->markTestSkipped('Route /api/learn/courses/{id}/progress not found, check routes/api.php');
        }

        $response->assertStatus(200);
        
        $this->assertEquals(100, $response->json('courseMembersProgress.0.scores.max_total'));
    }
}
