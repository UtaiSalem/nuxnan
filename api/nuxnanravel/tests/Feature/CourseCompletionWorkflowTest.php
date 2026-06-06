<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Models\Academy;
use App\Models\Student;
use App\Models\CourseGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCompletionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup initial data
        $this->admin = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $this->admin->id]);
        $this->course = Course::factory()->create([
            'user_id' => $this->admin->id,
            'academy_id' => $this->academy->id,
            'finalization_status' => 'active'
        ]);
        
        $this->studentUser = User::factory()->create();
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id
        ]);
        
        $this->member = CourseMember::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->studentUser->id,
            'completion_status' => 'active',
            'draft_grade' => 'A',
            'draft_total_score' => 95.0,
            'draft_grade_point' => 4.0
        ]);
    }

    public function test_finalize_after_publish_succeeds()
    {
        $this->course->update(['finalization_status' => 'published']);
        
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/courses/{$this->course->id}/completion/finalize");
            
        $response->assertStatus(200);
        $this->assertEquals('finalized', $this->course->fresh()->finalization_status);
    }

    public function test_finalize_promotes_all_eligible_members()
    {
        // Even without publish (meaning member is not pending_acceptance)
        $this->course->update(['finalization_status' => 'grading']);
        
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/courses/{$this->course->id}/completion/finalize");
            
        $response->assertStatus(200);
        
        $this->member->refresh();
        $this->assertEquals('completed', $this->member->completion_status);
        $this->assertEquals('A', $this->member->final_grade);
    }

    public function test_override_grade_updates_transcript()
    {
        $this->course->update(['finalization_status' => 'published']);
        
        // Finalize first to create transcript
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/courses/{$this->course->id}/completion/finalize");
            
        $this->assertDatabaseHas('course_grades', [
            'course_id' => $this->course->id,
            'user_id' => $this->studentUser->id,
            'letter_grade' => 'A'
        ]);
        
        // Now override grade
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/completion/members/{$this->member->id}/grade", [
                'grade' => 'B+',
                'score' => 88.0,
                'reason' => 'Recalculated'
            ]);
            
        $response->assertStatus(200);
        
        // Transcript should be updated
        $this->assertDatabaseHas('course_grades', [
            'course_id' => $this->course->id,
            'user_id' => $this->studentUser->id,
            'letter_grade' => 'B+'
        ]);
    }

    public function test_student_cannot_view_draft_during_grading()
    {
        $this->course->update(['finalization_status' => 'grading']);
        
        $response = $this->actingAs($this->studentUser, 'api')
            ->getJson("/api/courses/{$this->course->id}/completion/my-grade");
            
        $response->assertStatus(200);
        $this->assertFalse($response->json('data.can_view'));
        $this->assertNull($response->json('data.draft_grade'));
    }
}
