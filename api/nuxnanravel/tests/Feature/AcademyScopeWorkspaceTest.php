<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyScopeWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_unauthenticated_user_cannot_access_workspace()
    {
        $academy = Academy::factory()->create();

        $this->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=academy&scope_id={$academy->id}")
            ->assertStatus(401);
    }

    public function test_academy_owner_can_manage_scoped_tasks()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/scope/tasks", [
                'scope_type' => 'academy',
                'scope_id' => $academy->id,
                'title' => 'Test Academy Task',
                'description' => 'Test Description',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=academy&scope_id={$academy->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test Academy Task');
    }

    public function test_non_member_cannot_access_academy_scoped_tasks()
    {
        $academy = Academy::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=academy&scope_id={$academy->id}")
            ->assertStatus(403);
    }

    public function test_department_member_can_access_department_workspace()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $department = AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'Department Name',
            'type' => 'department',
        ]);

        $member = User::factory()->create();
        // Attach member to academy
        $academy->members()->attach($member->id, ['status' => 2]);
        // Attach member to department
        $department->members()->attach($member->id, ['role' => 'member']);

        // Check authorized access
        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=department&scope_id={$department->id}")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Check unauthorized outsider
        $outsider = User::factory()->create();
        $academy->members()->attach($outsider->id, ['status' => 2]);
        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=department&scope_id={$department->id}")
            ->assertStatus(403);
    }

    public function test_classroom_homeroom_teacher_and_student_can_access_classroom_workspace()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $teacher = User::factory()->create();

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'homeroom_teacher_id' => $teacher->id,
        ]);

        // Teacher access
        $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=classroom&scope_id={$classroom->id}")
            ->assertStatus(200);

        // Student access
        $studentUser = User::factory()->create();
        $academy->members()->attach($studentUser->id, ['status' => 2]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ใจดี',
            'nickname' => 'ชาย',
            'student_id' => 'STU001',
            'citizen_id' => '1234567890123',
            'status' => 'active',
            'class_level' => '1',
            'class_section' => '1',
        ]);

        \DB::table('classroom_students')->insert([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=classroom&scope_id={$classroom->id}")
            ->assertStatus(200);

        // Outsider access
        $outsider = User::factory()->create();
        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/tasks?scope_type=classroom&scope_id={$classroom->id}")
            ->assertStatus(403);
    }

    public function test_scoped_workspace_prevents_cross_academy_leakage()
    {
        $ownerA = User::factory()->create();
        $academyA = Academy::factory()->create(['user_id' => $ownerA->id]);

        $ownerB = User::factory()->create();
        $academyB = Academy::factory()->create(['user_id' => $ownerB->id]);

        $departmentB = AcademyGroup::create([
            'academy_id' => $academyB->id,
            'name' => 'Department B',
            'type' => 'department',
        ]);

        // Owner A tries to access department B (under Academy B) using Academy A endpoint
        $this->actingAs($ownerA, 'api')
            ->getJson("/api/academies/{$academyA->id}/scope/tasks?scope_type=department&scope_id={$departmentB->id}")
            ->assertStatus(404);
    }

    public function test_can_upload_files_to_scoped_workspace()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/scope/files", [
                'scope_type' => 'academy',
                'scope_id' => $academy->id,
                'file' => $file,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/scope/files?scope_type=academy&scope_id={$academy->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'document.pdf');
    }
}
