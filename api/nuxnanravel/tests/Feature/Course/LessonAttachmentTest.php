<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_attachment()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $this->actingAs($admin, 'api');

        $response = $this->postJson("/api/lessons/{$lesson->id}/attachments", [
            'files' => [$file],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attachments', [
            'attachable_id' => $lesson->id,
            'attachable_type' => Lesson::class,
            'original_name' => 'document.pdf',
        ]);

        $attachment = LessonAttachment::first();
        Storage::disk('local')->assertExists('course-materials/lessons/'.$attachment->filename);
    }

    public function test_non_admin_cannot_upload()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $this->actingAs($user, 'api');

        $response = $this->postJson("/api/lessons/{$lesson->id}/attachments", [
            'files' => [$file],
        ]);

        $response->assertStatus(403);
    }

    public function test_active_member_can_download()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create();
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 1,
        ]);

        $attachment = LessonAttachment::create([
            'course_id' => $course->id,
            'attachable_id' => $lesson->id,
            'attachable_type' => Lesson::class,
            'uploaded_by' => $admin->id,
            'filename' => 'fake_file.pdf',
            'original_name' => 'fake_file.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1000,
            'order' => 1,
            'download_count' => 0,
        ]);

        Storage::disk('local')->put('course-materials/lessons/'.$attachment->filename, 'content');

        $this->actingAs($student, 'api');

        $response = $this->get("/api/lessons/{$lesson->id}/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
        $this->assertEquals(1, $attachment->fresh()->download_count);
    }

    public function test_pending_member_cannot_download()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $student = User::factory()->create();
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 0,
        ]);

        $attachment = LessonAttachment::create([
            'course_id' => $course->id,
            'attachable_id' => $lesson->id,
            'attachable_type' => Lesson::class,
            'uploaded_by' => $admin->id,
            'filename' => 'fake_file.pdf',
            'original_name' => 'fake_file.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1000,
            'order' => 1,
            'download_count' => 0,
        ]);

        Storage::disk('local')->put('course-materials/lessons/'.$attachment->filename, 'content');

        $this->actingAs($student, 'api');

        $response = $this->getJson("/api/lessons/{$lesson->id}/attachments/{$attachment->id}/download");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_large_file_or_exe()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin, 'api');

        $largeFile = UploadedFile::fake()->create('large.pdf', 25000, 'application/pdf'); // > 20MB
        $exeFile = UploadedFile::fake()->create('virus.exe', 1000, 'application/x-msdownload');

        $response1 = $this->postJson("/api/lessons/{$lesson->id}/attachments", [
            'files' => [$largeFile],
        ]);
        $response1->assertStatus(422);

        $response2 = $this->postJson("/api/lessons/{$lesson->id}/attachments", [
            'files' => [$exeFile],
        ]);
        $response2->assertStatus(422);
    }

    public function test_admin_can_delete_attachment()
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $attachment = LessonAttachment::create([
            'course_id' => $course->id,
            'attachable_id' => $lesson->id,
            'attachable_type' => Lesson::class,
            'uploaded_by' => $admin->id,
            'filename' => 'fake_file_to_delete.pdf',
            'original_name' => 'fake_file_to_delete.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1000,
            'order' => 1,
            'download_count' => 0,
        ]);

        Storage::disk('local')->put('course-materials/lessons/'.$attachment->filename, 'content');

        $this->actingAs($admin, 'api');

        $response = $this->deleteJson("/api/lessons/{$lesson->id}/attachments/{$attachment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('lesson_attachments', [
            'id' => $attachment->id,
        ]);

        Storage::disk('local')->assertMissing('course-materials/lessons/'.$attachment->filename);
    }
}
