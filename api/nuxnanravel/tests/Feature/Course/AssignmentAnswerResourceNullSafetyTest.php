<?php

namespace Tests\Feature\Course;

use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentAnswerResourceNullSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_does_not_500_when_answer_owner_is_soft_deleted()
    {
        $teacher = User::factory()->create();
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $teacher->id]);

        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student1->id,
            'status' => 'active',
            'member_name' => 'Student One',
        ]);
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student2->id,
            'status' => 'active',
            'member_name' => 'Student Two',
        ]);

        $assignment = Assignment::factory()->create([
            'assignmentable_id' => $course->id,
            'assignmentable_type' => Course::class,
            'status' => 1,
            'points' => 10,
        ]);

        AssignmentAnswer::forceCreate([
            'assignment_id' => $assignment->id,
            'user_id' => $student1->id,
            'status' => 'submitted',
        ]);

        AssignmentAnswer::forceCreate([
            'assignment_id' => $assignment->id,
            'user_id' => $student2->id,
            'status' => 'submitted',
        ]);

        // Soft delete student 1
        $student1->delete();

        $this->actingAs($teacher, 'api');

        $response = $this->getJson("/api/assignments/{$assignment->id}/answers");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);

        // Find the deleted student's answer
        $deletedStudentAnswer = collect($data)->firstWhere('user_id', $student1->id);
        $this->assertNotNull($deletedStudentAnswer);
        $this->assertNull($deletedStudentAnswer['student']);
        $this->assertNotNull($deletedStudentAnswer['member_name']);
        $this->assertNotEquals(' ', $deletedStudentAnswer['member_name']);
        $this->assertNotEquals('', $deletedStudentAnswer['member_name']);
        $this->assertEquals('Student One', $deletedStudentAnswer['member_name']);
    }

    public function test_index_does_not_500_when_assignmentable_is_missing()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        // Create an assignment where assignmentable (Lesson) does not exist
        $assignment = Assignment::factory()->create([
            'assignmentable_id' => 99999,
            'assignmentable_type' => Lesson::class,
            'status' => 1, // published
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
        $this->assertNull($data[0]['course_group']);
    }

    public function test_member_name_falls_back_to_user_name_when_no_course_member_row()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $teacher->id]);

        // Note: No CourseMember row created for student

        $assignment = Assignment::factory()->create([
            'assignmentable_id' => $course->id,
            'assignmentable_type' => Course::class,
            'status' => 1,
            'points' => 10,
        ]);

        AssignmentAnswer::forceCreate([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($teacher, 'api');

        $response = $this->getJson("/api/assignments/{$assignment->id}/answers");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($student->name, $data[0]['member_name']);
    }
}
