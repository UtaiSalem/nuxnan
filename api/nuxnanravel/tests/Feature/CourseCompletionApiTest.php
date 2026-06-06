<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCompletionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test eligibility endpoint returns proper response
     */
    public function test_get_eligibility_status()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        // Get a member of this course
        $member = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$member) {
            $this->markTestSkipped('User is not a member of the course');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/eligibility");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'is_eligible',
                    'eligibility_status',
                    'stats',
                ]
            ]);
    }
    
    /**
     * Test eligibility summary endpoint
     */
    public function test_get_eligibility_summary()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/eligibility/summary");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'eligible',
                    'at_risk',
                    'ineligible',
                ]
            ]);
    }
    
    /**
     * Test completion summary endpoint
     */
    public function test_get_completion_summary()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/completion/summary");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'course_id',
                    'finalization_status',
                ]
            ]);
    }
    
    /**
     * Test my grade endpoint
     */
    public function test_get_my_grade()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $member = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$member) {
            $this->markTestSkipped('User is not a member of the course');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/completion/my-grade");
        
        $response->assertStatus(200);
    }
    
    /**
     * Test certificates list endpoint
     */
    public function test_get_certificates()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/certificates");
        
        $response->assertStatus(200);
    }
    
    /**
     * Test appeals list endpoint
     */
    public function test_get_appeals()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/appeals");
        
        $response->assertStatus(200);
    }
    
    /**
     * Test remediation sessions list endpoint
     */
    public function test_get_remediation_sessions()
    {
        $course = Course::first();
        $user = User::first();
        
        if (!$course || !$user) {
            $this->markTestSkipped('No course or user found in database');
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/courses/{$course->id}/remediation");
        
        $response->assertStatus(200);
    }
}
