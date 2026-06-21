<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLifecycleControllerTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private Academy $otherAcademy;

    private User $owner;

    private User $teacher;

    private User $randomUser;

    private User $studentUser;

    private Student $student;

    private Student $otherAcademyStudent;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private Classroom $classroomA;

    private Classroom $classroomB;

    private Classroom $classroomC;

    private Classroom $otherAcademyClassroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->teacher = User::factory()->create();
        $this->randomUser = User::factory()->create();
        $this->studentUser = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Lifecycle Academy',
            'slug' => 'lifecycle-academy',
        ]);

        $this->otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Lifecycle Academy',
            'slug' => 'other-lifecycle-academy',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacher->id,
            'role' => 'teacher',
        ]);

        $this->year2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->year2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
            'homeroom_teacher_id' => $this->teacher->id,
        ]);

        $this->classroomB = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomC = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $otherYear = AcademicYear::create([
            'academy_id' => $this->otherAcademy->id,
            'name' => '3568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->otherAcademyClassroom = Classroom::create([
            'academy_id' => $this->otherAcademy->id,
            'academic_year_id' => $otherYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'Other ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->studentUser->id,
            'first_name_th' => 'สมหญิง',
            'last_name_th' => 'ตัวอย่าง',
            'student_id' => 'LC001',
            'citizen_id' => '1111111111111',
            'status' => 'active',
        ]);

        $this->otherAcademyStudent = Student::create([
            'academy_id' => $this->otherAcademy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'คนอื่น',
            'last_name_th' => 'ต่างโรงเรียน',
            'student_id' => 'LC999',
            'citizen_id' => '9999999999999',
            'status' => 'active',
        ]);
    }

    public function test_graduate_as_academy_admin_returns_closed_enrollment_and_student_summary(): void
    {
        $this->enrollStudentInA();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('graduate'), ['reason' => 'เรียนจบ']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('closed_enrollment.status', ClassroomStudent::STATUS_GRADUATED)
            ->assertJsonPath('new_enrollment', null)
            ->assertJsonPath('student.status', 'graduated');
    }

    public function test_graduate_as_homeroom_teacher_is_allowed(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->teacher, 'api')
            ->postJson($this->studentUrl('graduate'), ['reason' => 'จบแล้ว'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_graduate_as_random_user_is_forbidden(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->randomUser, 'api')
            ->postJson($this->studentUrl('graduate'), ['reason' => 'x'])
            ->assertForbidden();
    }

    public function test_drop_without_reason_returns_validation_error(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('drop'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_drop_with_reason_marks_student_inactive(): void
    {
        $this->enrollStudentInA();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('drop'), ['reason' => 'ลาออกกลางคัน']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('closed_enrollment.status', ClassroomStudent::STATUS_DROPPED)
            ->assertJsonPath('new_enrollment', null)
            ->assertJsonPath('student.status', 'inactive');
    }

    public function test_drop_unauthenticated_returns_unauthorized(): void
    {
        $this->enrollStudentInA();

        $this->postJson($this->studentUrl('drop'), ['reason' => 'ลาออก'])
            ->assertUnauthorized();
    }

    public function test_repeat_rejects_classroom_from_other_academy(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('repeat'), ['new_classroom_id' => $this->otherAcademyClassroom->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['new_classroom_id']);
    }

    public function test_repeat_same_classroom_returns_422_from_service_exception(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('repeat'), ['new_classroom_id' => $this->classroomA->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_repeat');
    }

    public function test_repeat_happy_path_returns_new_enrollment(): void
    {
        $this->enrollStudentInA();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('repeat'), [
                'new_classroom_id' => $this->classroomB->id,
                'student_number' => 15,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('closed_enrollment.status', ClassroomStudent::STATUS_REPEATING)
            ->assertJsonPath('new_enrollment.classroom_id', $this->classroomB->id)
            ->assertJsonPath('new_enrollment.student_number', 15);
    }

    public function test_promote_same_year_returns_validation_error(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('promote'), [
                'from_classroom_id' => $this->classroomA->id,
                'to_classroom_id' => $this->classroomB->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_classroom_id']);
    }

    public function test_promote_happy_path_updates_two_rows(): void
    {
        $this->enrollStudentInA();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('promote'), [
                'from_classroom_id' => $this->classroomA->id,
                'to_classroom_id' => $this->classroomC->id,
                'student_number' => 3,
            ]);

        $response->assertOk()
            ->assertJsonPath('closed_enrollment.status', ClassroomStudent::STATUS_PROMOTED)
            ->assertJsonPath('new_enrollment.classroom_id', $this->classroomC->id)
            ->assertJsonPath('student.class_level', '2');
    }

    public function test_transfer_cross_year_returns_validation_error(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('transfer'), [
                'from_classroom_id' => $this->classroomA->id,
                'to_classroom_id' => $this->classroomC->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_classroom_id']);
    }

    public function test_transfer_happy_path_returns_new_enrollment(): void
    {
        $this->enrollStudentInA();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('transfer'), [
                'from_classroom_id' => $this->classroomA->id,
                'to_classroom_id' => $this->classroomB->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('closed_enrollment.status', ClassroomStudent::STATUS_TRANSFERRED)
            ->assertJsonPath('new_enrollment.classroom_id', $this->classroomB->id)
            ->assertJsonPath('student.class_section', '2');
    }

    public function test_history_returns_enrollment_list_with_loaded_classroom_data(): void
    {
        $this->enrollStudentInA();
        $this->actingAs($this->owner, 'api')
            ->postJson($this->studentUrl('transfer'), [
                'from_classroom_id' => $this->classroomA->id,
                'to_classroom_id' => $this->classroomB->id,
            ])
            ->assertOk();

        $response = $this->actingAs($this->owner, 'api')
            ->getJson($this->studentUrl('enrollment-history-v2'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.academic_year.name', '2568');

        $classroomNames = collect($response->json('data'))
            ->pluck('classroom.display_name')
            ->all();

        $this->assertContains('ม.1/1', $classroomNames);
        $this->assertContains('ม.1/2', $classroomNames);
    }

    public function test_cross_academy_student_route_scope_returns_404(): void
    {
        $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/students/{$this->otherAcademyStudent->id}/enrollment-history-v2")
            ->assertNotFound();
    }

    public function test_history_as_student_themselves_is_forbidden(): void
    {
        $this->enrollStudentInA();

        $this->actingAs($this->studentUser, 'api')
            ->getJson($this->studentUrl('enrollment-history-v2'))
            ->assertForbidden();
    }

    private function studentUrl(string $action): string
    {
        return "/api/academies/{$this->academy->id}/students/{$this->student->id}/{$action}";
    }

    private function enrollStudentInA(): void
    {
        ClassroomStudent::query()->delete();
        $this->student->update([
            'status' => 'active',
            'class_level' => null,
            'class_section' => null,
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->classroomA->academic_year_id,
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'student_number' => 10,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth(),
        ]);
    }
}
