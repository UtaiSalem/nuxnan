<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentAnswerAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function createCourseWithAssignment()
    {
        $admin = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $admin->id,
            'instructor_id' => $admin->id,
            'status' => 1,
        ]);

        $course->courseMembers()->create([
            'user_id' => $admin->id,
            'role' => 3, // admin/owner
            'status' => 1,
        ]);

        $assignment = Assignment::create([
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
            'title' => 'Test Assignment',
            'points' => 10,
            'status' => 1,
        ]);

        return [$admin, $course, $assignment];
    }

    private function createStudent($course)
    {
        $student = User::factory()->create();
        $course->courseMembers()->create([
            'user_id' => $student->id,
            'role' => 1, // student
            'status' => 1,
        ]);

        return $student;
    }

    public function test_student_can_attach_a_pdf_to_an_answer()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('งานส่ง.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'My answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('assignment_answer_attachments', [
            'original_name' => 'งานส่ง.pdf',
        ]);

        $answer = AssignmentAnswer::where('user_id', $student->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        Storage::disk('local')->assertExists('course-materials/assignment-answers/'.$attachment->filename);
    }

    public function test_executable_upload_is_rejected()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('shell.php', 10);

        $response = $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Hacked',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseEmpty('assignment_answer_attachments');
    }

    public function test_image_field_rejects_a_non_image()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

        $response = $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Doc in image field',
            'course_id' => $course->id,
            'images' => [$file],
        ]);

        $response->assertStatus(422);
    }

    public function test_owner_can_download_their_own_attachment()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('งานส่ง.pdf', 100, 'application/pdf');

        $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'My answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $answer = AssignmentAnswer::where('user_id', $student->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        $response = $this->actingAs($student, 'api')->getJson("/api/assignments/{$assignment->id}/answers/{$answer->id}/attachments/{$attachment->id}/download?course_id={$course->id}");

        $response->assertStatus(200);
    }

    public function test_another_student_cannot_download_someone_elses_attachment()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student1 = $this->createStudent($course);
        $student2 = $this->createStudent($course);

        $file = UploadedFile::fake()->create('งานส่ง.pdf', 100, 'application/pdf');

        $this->actingAs($student1, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Student 1 answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $answer = AssignmentAnswer::where('user_id', $student1->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        $response = $this->actingAs($student2, 'api')->getJson("/api/assignments/{$assignment->id}/answers/{$answer->id}/attachments/{$attachment->id}/download?course_id={$course->id}");

        $response->assertStatus(403);
    }

    public function test_course_admin_can_download_a_students_attachment()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('งานส่ง.pdf', 100, 'application/pdf');

        $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Student answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $answer = AssignmentAnswer::where('user_id', $student->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        $response = $this->actingAs($admin, 'api')->getJson("/api/assignments/{$assignment->id}/answers/{$answer->id}/attachments/{$attachment->id}/download?course_id={$course->id}");

        $response->assertStatus(200);
    }

    public function test_attachment_id_from_another_answer_is_rejected()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student1 = $this->createStudent($course);
        $student2 = $this->createStudent($course);

        $file1 = UploadedFile::fake()->create('งานส่ง1.pdf', 100, 'application/pdf');
        $this->actingAs($student1, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Student 1',
            'course_id' => $course->id,
            'attachments' => [$file1],
        ]);
        $answer1 = AssignmentAnswer::where('user_id', $student1->id)->where('assignment_id', $assignment->id)->first();
        $attachment1 = $answer1->attachments->first();

        $file2 = UploadedFile::fake()->create('งานส่ง2.pdf', 100, 'application/pdf');
        $this->actingAs($student2, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Student 2',
            'course_id' => $course->id,
            'attachments' => [$file2],
        ]);
        $answer2 = AssignmentAnswer::where('user_id', $student2->id)->where('assignment_id', $assignment->id)->first();
        $attachment2 = $answer2->attachments->first();

        // Request answer A's URL with answer B's attachment id
        $response = $this->actingAs($student1, 'api')->getJson("/api/assignments/{$assignment->id}/answers/{$answer1->id}/attachments/{$attachment2->id}/download?course_id={$course->id}");

        $response->assertStatus(404);
    }

    public function test_course_id_query_param_cannot_be_spoofed_to_gain_admin_rights()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $victim = $this->createStudent($course);
        $attacker = $this->createStudent($course);

        // The attacker owns an unrelated course of their own, so they are an admin *somewhere*.
        $attackerOwnCourse = Course::factory()->create([
            'user_id' => $attacker->id,
            'instructor_id' => $attacker->id,
            'status' => 1,
        ]);

        $file = UploadedFile::fake()->create('ความลับ.pdf', 100, 'application/pdf');
        $this->actingAs($victim, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Victim answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ]);

        $answer = AssignmentAnswer::where('user_id', $victim->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        // Attacker points course_id at their OWN course to fake the isAdmin() check.
        $response = $this->actingAs($attacker, 'api')->getJson("/api/assignments/{$assignment->id}/answers/{$answer->id}/attachments/{$attachment->id}/download?course_id={$attackerOwnCourse->id}");

        $response->assertStatus(403);
    }

    public function test_a_student_listing_answers_only_sees_their_own()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $victim = $this->createStudent($course);
        $nosy = $this->createStudent($course);

        $this->actingAs($victim, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'SECRET-VICTIM-ANSWER',
            'course_id' => $course->id,
        ])->assertStatus(200);

        $this->actingAs($nosy, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'NOSY-OWN-ANSWER',
            'course_id' => $course->id,
        ])->assertStatus(200);

        $response = $this->actingAs($nosy, 'api')->getJson("/api/assignments/{$assignment->id}/answers?course_id={$course->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['content' => 'NOSY-OWN-ANSWER']);
        $response->assertJsonMissing(['content' => 'SECRET-VICTIM-ANSWER']);
    }

    public function test_a_course_admin_listing_answers_still_sees_every_student()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student1 = $this->createStudent($course);
        $student2 = $this->createStudent($course);

        $this->actingAs($student1, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Answer one',
            'course_id' => $course->id,
        ]);
        $this->actingAs($student2, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Answer two',
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/assignments/{$assignment->id}/answers?course_id={$course->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_listing_cannot_be_widened_by_spoofing_course_id()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $victim = $this->createStudent($course);
        $attacker = $this->createStudent($course);

        // The attacker owns an unrelated course, so they are an admin somewhere.
        $attackerOwnCourse = Course::factory()->create([
            'user_id' => $attacker->id,
            'instructor_id' => $attacker->id,
            'status' => 1,
        ]);

        $this->actingAs($victim, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'SECRET-VICTIM-ANSWER',
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($attacker, 'api')->getJson("/api/assignments/{$assignment->id}/answers?course_id={$attackerOwnCourse->id}");

        $response->assertStatus(200);
        $response->assertJsonMissing(['content' => 'SECRET-VICTIM-ANSWER']);
    }

    public function test_a_student_can_delete_their_own_answer()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $student = $this->createStudent($course);

        $file = UploadedFile::fake()->create('งานส่ง.pdf', 100, 'application/pdf');
        $this->actingAs($student, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'My answer',
            'course_id' => $course->id,
            'attachments' => [$file],
        ])->assertStatus(200);

        $answer = AssignmentAnswer::where('user_id', $student->id)->where('assignment_id', $assignment->id)->first();
        $attachment = $answer->attachments->first();

        $response = $this->actingAs($student, 'api')->deleteJson("/api/assignments/{$assignment->id}/answers/{$answer->id}?course_id={$course->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('assignment_answers', ['id' => $answer->id]);
        $this->assertDatabaseMissing('assignment_answer_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing('course-materials/assignment-answers/'.$attachment->filename);
    }

    public function test_a_student_cannot_delete_someone_elses_answer()
    {
        [$admin, $course, $assignment] = $this->createCourseWithAssignment();
        $victim = $this->createStudent($course);
        $attacker = $this->createStudent($course);

        $this->actingAs($victim, 'api')->postJson("/api/assignments/{$assignment->id}/answers", [
            'content' => 'Victim answer',
            'course_id' => $course->id,
        ]);

        $answer = AssignmentAnswer::where('user_id', $victim->id)->where('assignment_id', $assignment->id)->first();

        $response = $this->actingAs($attacker, 'api')->deleteJson("/api/assignments/{$assignment->id}/answers/{$answer->id}?course_id={$course->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('assignment_answers', ['id' => $answer->id]);
    }
}
