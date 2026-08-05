<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassroomService;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassroomDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $academy = Academy::create(['user_id' => $user->id, 'name' => 'Test', 'slug' => 'test-'.uniqid()]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id, 'name' => '2569', 'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        return [$academy, $year];
    }

    private function classroom(Academy $academy, AcademicYear $year, string $section): Classroom
    {
        return Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $year->id, 'grade_level' => 'ม.4',
            'section' => $section, 'name' => "ม.4/{$section}", 'capacity' => 50, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function student(Academy $academy, string $id): Student
    {
        return Student::create([
            'user_id' => User::factory()->create()->id, 'academy_id' => $academy->id,
            'first_name_th' => 'Test', 'last_name_th' => 'Student', 'student_id' => $id,
            'citizen_id' => str_pad($id, 13, '0'),
        ]);
    }

    public function test_active_enrollment_rejects_classroom_deletion(): void
    {
        [$academy, $year] = $this->context();
        $room = $this->classroom($academy, $year, '9');
        $enrollment = app(StudentEnrollmentService::class)->enrollStudent($this->student($academy, 'S1'), $room);

        $thrown = false;
        try {
            app(ClassroomService::class)->deleteClassroom($room);
        } catch (ValidationException) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
        $this->assertDatabaseHas('classrooms', ['id' => $room->id]);
        $this->assertDatabaseHas('classroom_students', ['id' => $enrollment->id, 'status' => 'active']);
    }

    public function test_deletion_removes_historical_enrollments(): void
    {
        [$academy, $year] = $this->context();
        $room = $this->classroom($academy, $year, '9');
        $student = $this->student($academy, 'S2');
        ClassroomStudent::create([
            'academy_id' => $academy->id, 'classroom_id' => $room->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'status' => ClassroomStudent::STATUS_TRANSFERRED,
        ]);

        app(ClassroomService::class)->deleteClassroom($room);

        $this->assertDatabaseMissing('classrooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('classroom_students', ['classroom_id' => $room->id]);
    }

    public function test_removing_last_enrollment_clears_cached_class_fields(): void
    {
        [$academy, $year] = $this->context();
        $room = $this->classroom($academy, $year, '9');
        $student = $this->student($academy, 'S3');
        app(StudentEnrollmentService::class)->enrollStudent($student, $room);

        app(StudentEnrollmentService::class)->removeFromClassroom($student, $room);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'class_level' => null, 'class_section' => null]);
    }

    public function test_transfer_updates_cached_class_fields_to_destination(): void
    {
        [$academy, $year] = $this->context();
        $from = $this->classroom($academy, $year, '9');
        $to = $this->classroom($academy, $year, '10');
        $student = $this->student($academy, 'S4');
        app(StudentEnrollmentService::class)->enrollStudent($student, $from);

        app(StudentEnrollmentService::class)->transferStudent($student, $from, $to);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'class_level' => '4', 'class_section' => '10']);
    }
}
