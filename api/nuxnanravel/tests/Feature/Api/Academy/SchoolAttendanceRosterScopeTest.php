<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Learn\Academy\SchoolAttendance;
use App\Models\Learn\Academy\SchoolAttendanceRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolAttendanceRosterScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    private Classroom $classroom;

    private SchoolAttendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Attendance Scope Academy',
            'slug' => 'attendance-scope-academy',
        ]);

        // classrooms.academic_year_id became NOT NULL in migration
        // 2026_07_12_150000_fix_classrooms_unique_and_notnull, after this test was written.
        $academicYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);

        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->attendance = SchoolAttendance::create([
            'academy_id' => $this->academy->id,
            'date' => '2026-06-21',
            'title' => 'เช็คชื่อหน้าเสาธง',
            'status' => 'open',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_show_enriches_active_student_with_classroom_info(): void
    {
        [$user, $student] = $this->makeMemberedStudent('ATD001', 'สมศรี');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $student->id,
            'student_number' => 12,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        SchoolAttendanceRecord::create([
            'attendance_id' => $this->attendance->id,
            'academy_id' => $this->academy->id,
            'student_id' => $user->id,
            'status' => 'present',
            'check_in_method' => 'manual',
            'checked_in_at' => now(),
            'recorded_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/{$this->attendance->id}");

        $response->assertOk()
            ->assertJsonPath('data.records.0.student_number', 12)
            ->assertJsonPath('data.records.0.classroom_name', 'ม.1/1');
    }

    public function test_show_does_not_enrich_transferred_student_with_stale_classroom_info(): void
    {
        [$user, $student] = $this->makeMemberedStudent('ATD002', 'สมหมาย');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $student->id,
            'student_number' => 44,
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
            'enrolled_at' => '2026-05-20',
            'left_at' => '2026-06-10',
        ]);

        SchoolAttendanceRecord::create([
            'attendance_id' => $this->attendance->id,
            'academy_id' => $this->academy->id,
            'student_id' => $user->id,
            'status' => 'present',
            'check_in_method' => 'manual',
            'checked_in_at' => now(),
            'recorded_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/{$this->attendance->id}");

        $response->assertOk()
            ->assertJsonPath('data.records.0.student_number', null)
            ->assertJsonPath('data.records.0.classroom_name', null);
    }

    public function test_bulk_manual_record_persists_the_teacher_remark(): void
    {
        [$user] = $this->makeMemberedStudent('ATD003', 'สมปอง');

        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/school-attendances/{$this->attendance->id}/records", [
                'records' => [
                    ['student_id' => $user->id, 'status' => 'late', 'remarks' => 'รถติดมาสาย 10 นาที'],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('school_attendance_records', [
            'attendance_id' => $this->attendance->id,
            'student_id' => $user->id,
            'status' => 'late',
            'remarks' => 'รถติดมาสาย 10 นาที',
        ]);
    }

    // The 2026_06_25_090000 rename made `remark` an unknown key, and an unknown key used to be
    // accepted and dropped. Answer 422 instead, so the next rename cannot go quiet for weeks.
    public function test_bulk_records_rejects_the_pre_rename_remark_key(): void
    {
        [$user] = $this->makeMemberedStudent('ATD009', 'สมหญิง');

        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/school-attendances/{$this->attendance->id}/records", [
                'records' => [
                    ['student_id' => $user->id, 'status' => 'late', 'remark' => 'รถติด'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('records.0');

        // 'records.0' is a flat key, so it cannot be reached with dot-path lookup.
        // The message must name the key sent and the keys read, or it does not help the next person.
        $errors = $response->json('errors');
        $this->assertStringContainsString('ฟิลด์ที่ไม่รู้จัก: remark', $errors['records.0'][0]);
        $this->assertStringContainsString('student_id, status, remarks', $errors['records.0'][0]);
        $this->assertDatabaseMissing('school_attendance_records', [
            'attendance_id' => $this->attendance->id,
            'student_id' => $user->id,
        ]);
    }

    public function test_resubmitting_bulk_records_updates_the_existing_remark(): void
    {
        [$user] = $this->makeMemberedStudent('ATD004', 'สมทรง');
        $url = "/api/academies/{$this->academy->id}/school-attendances/{$this->attendance->id}/records";

        $this->actingAs($this->owner, 'api')->postJson($url, [
            'records' => [['student_id' => $user->id, 'status' => 'absent', 'remarks' => 'ยังไม่ทราบสาเหตุ']],
        ])->assertOk();

        $this->actingAs($this->owner, 'api')->postJson($url, [
            'records' => [['student_id' => $user->id, 'status' => 'leave', 'remarks' => 'ผู้ปกครองแจ้งลาป่วย']],
        ])->assertOk();

        $this->assertSame(1, SchoolAttendanceRecord::where('attendance_id', $this->attendance->id)
            ->where('student_id', $user->id)->count());

        $this->assertDatabaseHas('school_attendance_records', [
            'attendance_id' => $this->attendance->id,
            'student_id' => $user->id,
            'status' => 'leave',
            'remarks' => 'ผู้ปกครองแจ้งลาป่วย',
        ]);
    }

    private function makeMemberedStudent(string $studentId, string $firstName): array
    {
        $user = User::factory()->create();

        $student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'first_name_th' => $firstName,
            'last_name_th' => 'ตัวอย่าง',
            'student_id' => $studentId,
            'citizen_id' => str_pad((string) random_int(1, 9999999999999), 13, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'student_id' => $student->id,
            'role' => 'student',
            'status' => 2,
        ]);

        return [$user, $student];
    }
}
