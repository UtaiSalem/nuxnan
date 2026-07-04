<?php

namespace Tests\Feature\Api\Academy;

use App\Jobs\ProcessStudentImportBatchJob;
use App\Jobs\ValidateStudentImportBatchJob;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\StudentImportBatch;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    private AcademicYear $year;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();

        $this->owner = User::factory()->create();
        $this->academy = Academy::create(['user_id' => $this->owner->id, 'name' => 'Import Academy', 'slug' => 'import-academy']);
        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
        ]);
    }

    public function test_upload_is_idempotent_and_dispatches_validation(): void
    {
        $response = $this->actingAs($this->owner, 'api')->post($this->url(), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $this->validCsv()),
            'academic_year_id' => $this->year->id,
            'idempotency_key' => 'same-upload',
        ]);

        $response->assertAccepted()->assertJsonPath('data.status', 'uploaded');
        Queue::assertPushed(ValidateStudentImportBatchJob::class);

        $second = $this->actingAs($this->owner, 'api')->post($this->url(), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $this->validCsv()),
            'academic_year_id' => $this->year->id,
            'idempotency_key' => 'same-upload',
        ]);
        $second->assertOk()->assertJsonPath('data.id', $response->json('data.id'));
        $this->assertDatabaseCount('student_import_batches', 1);
    }

    public function test_validate_preview_and_process_reuse_single_intake_service(): void
    {
        $batch = $this->createBatch($this->validCsv());
        app(StudentImportService::class)->validateBatch($batch);

        $batch->refresh();
        $this->assertSame('validated', $batch->status);
        $this->assertSame(2, $batch->valid_rows);
        $this->assertSame(0, $batch->invalid_rows);

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url("/{$batch->id}/rows"))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url("/{$batch->id}/confirm"))
            ->assertAccepted()
            ->assertJsonPath('data.status', 'processing');
        Queue::assertPushed(ProcessStudentImportBatchJob::class);

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url("/{$batch->id}/confirm"))
            ->assertConflict();

        app(StudentImportService::class)->processBatch($batch->fresh());
        $batch->refresh();
        $this->assertSame('completed', $batch->status);
        $this->assertSame(2, $batch->imported_rows);
        $this->assertDatabaseHas('students', ['academy_id' => $this->academy->id, 'student_id' => 'IMP001']);
        $this->assertDatabaseHas('classroom_students', ['classroom_id' => $this->classroom->id, 'student_number' => 1]);
        $this->assertDatabaseHas('guardian_contacts', ['contact_type' => 'mobile', 'contact_value' => '0811111111']);
    }

    public function test_validation_marks_bad_and_duplicate_rows_invalid(): void
    {
        $csv = "student_code,first_name_th,last_name_th,grade_level,section,student_number\n".
            "DUP001,One,Student,ม.1,1,1\n".
            "DUP001,Two,Student,ม.1,1,2\n".
            ",Missing,Code,ม.9,9,3\n";
        $batch = $this->createBatch($csv);

        app(StudentImportService::class)->validateBatch($batch);

        $batch->refresh();
        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertSame(2, $batch->invalid_rows);
        $this->assertDatabaseHas('student_import_rows', ['batch_id' => $batch->id, 'row_number' => 3, 'status' => 'invalid']);
    }

    public function test_cross_academy_batch_is_not_visible(): void
    {
        $otherOwner = User::factory()->create();
        $other = Academy::create(['user_id' => $otherOwner->id, 'name' => 'Other', 'slug' => 'other-import']);
        $batch = $this->createBatch($this->validCsv());

        $this->actingAs($otherOwner, 'api')
            ->getJson("/api/academies/{$other->id}/student-imports/{$batch->id}")
            ->assertNotFound();
    }

    public function test_user_without_import_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson($this->url())
            ->assertForbidden();
    }

    private function createBatch(string $csv): StudentImportBatch
    {
        $path = "student-imports/{$this->academy->id}/test.csv";
        Storage::disk('local')->put($path, $csv);

        return StudentImportBatch::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'filename' => $path,
            'original_filename' => 'test.csv',
            'status' => 'uploaded',
            'idempotency_key' => hash('sha256', uniqid('', true)),
            'created_by' => $this->owner->id,
            'confirmed_by' => $this->owner->id,
            'confirmed_at' => now(),
        ]);
    }

    private function validCsv(): string
    {
        return "student_code,title_th,first_name_th,last_name_th,citizen_id,date_of_birth,gender,grade_level,section,student_number,enrollment_date,previous_school,guardian_name,guardian_phone,guardian_relation\n".
            "IMP001,เด็กชาย,หนึ่ง,ทดสอบ,,2013-01-01,ชาย,ม.1,1,1,2026-05-16,เดิม,พ่อ หนึ่ง,0811111111,father\n".
            "IMP002,เด็กหญิง,สอง,ทดสอบ,,2013-02-01,หญิง,ม.1,1,2,2026-05-16,เดิม,แม่ สอง,0822222222,mother\n";
    }

    private function url(string $suffix = ''): string
    {
        return "/api/academies/{$this->academy->id}/student-imports{$suffix}";
    }
}
