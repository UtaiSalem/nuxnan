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

class AcademyMemberFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Academy Member Filters',
            'slug' => 'academy-member-filters',
        ]);
    }

    public function test_filter_options_use_active_current_year_enrollments_over_student_snapshot(): void
    {
        $currentYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $previousYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
            'is_current' => false,
        ]);

        $currentClassroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $currentYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $oldClassroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $previousYear->id,
            'grade_level' => 'ม.9',
            'section' => '9',
            'name' => 'ม.9/9',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ตัวอย่าง',
            'student_id' => 'FLT001',
            'citizen_id' => '1111111111111',
            'status' => 'active',
            'class_level' => 'legacy-level',
            'class_section' => 'legacy-room',
            'gender' => 1,
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $student->user_id,
            'student_id' => $student->id,
            'role' => 'student',
            'status' => 2,
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $previousYear->id,
            'classroom_id' => $oldClassroom->id,
            'student_id' => $student->id,
            'student_number' => 8,
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
            'enrolled_at' => '2025-05-20',
            'left_at' => '2025-09-01',
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $currentYear->id,
            'classroom_id' => $currentClassroom->id,
            'student_id' => $student->id,
            'student_number' => 3,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members/filter-options");

        $response->assertOk()
            ->assertJsonPath('filters.class_levels.0.value', 'ม.1')
            ->assertJsonPath('filters.class_sections.0.value', '1')
            ->assertJsonPath('filters.classrooms.0.class_level', 'ม.1')
            ->assertJsonPath('filters.classrooms.0.class_section', '1')
            ->assertJsonPath('filters.classrooms.0.count', 1);

        $classLevels = collect($response->json('filters.class_levels'))->pluck('value')->all();
        $this->assertNotContains('legacy-level', $classLevels);
    }

    public function test_filter_options_fall_back_to_student_snapshot_when_no_current_year_exists(): void
    {
        $student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'สายฝน',
            'last_name_th' => 'ตัวอย่าง',
            'student_id' => 'FLT002',
            'citizen_id' => '2222222222222',
            'status' => 'active',
            'class_level' => 'ม.2',
            'class_section' => '5',
            'gender' => 0,
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $student->user_id,
            'student_id' => $student->id,
            'role' => 'student',
            'status' => 2,
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members/filter-options");

        $response->assertOk()
            ->assertJsonPath('filters.class_levels.0.value', 'ม.2')
            ->assertJsonPath('filters.class_sections.0.value', '5')
            ->assertJsonPath('filters.classrooms.0.class_level', 'ม.2')
            ->assertJsonPath('filters.classrooms.0.class_section', '5')
            ->assertJsonPath('filters.classrooms.0.count', 1);
    }
}
