<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ClassroomEnrollmentListTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    private Academy $otherAcademy;

    private AcademicYear $currentYear;

    private AcademicYear $previousYear;

    private Classroom $classroom;

    private Classroom $otherAcademyClassroom;

    private Student $activeStudent;

    private Student $transferredStudent;

    private Student $graduatedStudent;

    private Student $legacyYearStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Enrollment List Academy',
            'slug' => 'enrollment-list-academy',
        ]);

        $this->otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Enrollment Academy',
            'slug' => 'other-enrollment-academy',
        ]);

        $this->currentYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->previousYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $otherYear = AcademicYear::create([
            'academy_id' => $this->otherAcademy->id,
            'name' => '3569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->currentYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->otherAcademyClassroom = Classroom::create([
            'academy_id' => $this->otherAcademy->id,
            'academic_year_id' => $otherYear->id,
            'grade_level' => 'ม.1',
            'section' => '9',
            'name' => 'Other ม.1/9',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->activeStudent = $this->makeStudent('ENR001', 'สมใจ');
        $this->transferredStudent = $this->makeStudent('ENR002', 'พิมพ์');
        $this->graduatedStudent = $this->makeStudent('ENR003', 'กานต์');
        $this->legacyYearStudent = $this->makeStudent('ENR004', 'อิงฟ้า');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->currentYear->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->activeStudent->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
            'created_by_user_id' => $this->owner->id,
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->currentYear->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->transferredStudent->id,
            'student_number' => 2,
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
            'enrolled_at' => '2026-05-20',
            'left_at' => '2026-06-10',
            'leave_reason' => 'ย้ายห้อง',
            'created_by_user_id' => $this->owner->id,
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->currentYear->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->graduatedStudent->id,
            'student_number' => 3,
            'status' => ClassroomStudent::STATUS_GRADUATED,
            'enrolled_at' => '2026-05-20',
            'left_at' => '2026-06-18',
            'leave_reason' => 'จบการศึกษา',
            'created_by_user_id' => $this->owner->id,
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->previousYear->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->legacyYearStudent->id,
            'student_number' => 4,
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
            'enrolled_at' => '2025-05-20',
            'left_at' => '2025-10-01',
            'leave_reason' => 'เก่าย้อนหลัง',
            'created_by_user_id' => $this->owner->id,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson($this->endpointUrl())
            ->assertUnauthorized();
    }

    public function test_defaults_to_active_status_only(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->endpointUrl());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student.student_id', 'ENR001')
            ->assertJsonPath('data.0.status', ClassroomStudent::STATUS_ACTIVE);
    }

    public function test_can_filter_transferred_only(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->endpointUrl([
                'status' => [ClassroomStudent::STATUS_TRANSFERRED],
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $studentIds = collect($response->json('data'))
            ->pluck('student.student_id')
            ->all();

        $this->assertSame(['ENR002', 'ENR004'], $studentIds);
    }

    public function test_can_filter_multiple_statuses(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->endpointUrl([
                'status' => [
                    ClassroomStudent::STATUS_ACTIVE,
                    ClassroomStudent::STATUS_GRADUATED,
                ],
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $studentIds = collect($response->json('data'))
            ->pluck('student.student_id')
            ->all();

        $this->assertSame(['ENR001', 'ENR003'], $studentIds);
    }

    public function test_can_filter_by_academic_year_id(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->endpointUrl([
                'status' => [ClassroomStudent::STATUS_TRANSFERRED],
                'academic_year_id' => $this->currentYear->id,
            ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student.student_id', 'ENR002');
    }

    public function test_cross_academy_classroom_scope_returns_404(): void
    {
        $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/classrooms/{$this->otherAcademyClassroom->id}/enrollments")
            ->assertNotFound();
    }

    public function test_response_contains_student_summary_status_text_and_created_by_shape(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->endpointUrl([
                'status' => [ClassroomStudent::STATUS_GRADUATED],
            ]));

        $response->assertOk()
            ->assertJsonPath('data.0.status_text', 'จบการศึกษา')
            ->assertJsonPath('data.0.student.first_name_th', 'กานต์')
            ->assertJsonPath('data.0.classroom.display_name', 'ม.1/1')
            ->assertJsonPath('data.0.created_by.id', $this->owner->id)
            ->assertJsonPath('data.0.created_by.name', $this->owner->name);
    }

    private function endpointUrl(array $query = []): string
    {
        $base = "/api/academies/{$this->academy->id}/classrooms/{$this->classroom->id}/enrollments";

        if ($query === []) {
            return $base;
        }

        return $base.'?'.Arr::query($query);
    }

    private function makeStudent(string $studentId, string $firstName): Student
    {
        return Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => $firstName,
            'last_name_th' => 'ตัวอย่าง',
            'student_id' => $studentId,
            'citizen_id' => str_pad((string) random_int(1, 9999999999999), 13, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }
}
