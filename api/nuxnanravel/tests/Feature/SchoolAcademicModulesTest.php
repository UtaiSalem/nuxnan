<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolAcademicModulesTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $user->id]);

        return [$academy, $user];
    }

    private function academyWithMember(array $permissions = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    public function test_owner_can_crud_subjects(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        // Create
        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/subjects", [
                'name_th' => 'คณิตศาสตร์',
                'subject_code' => 'MATH101',
                'credits' => 3,
                'subject_type' => 'required',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subjects', [
            'academy_id' => $academy->id,
            'name_th' => 'คณิตศาสตร์',
            'subject_code' => 'MATH101',
        ]);

        // List
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/subjects");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name_th' => 'คณิตศาสตร์']);

        // Update
        $subject = Subject::where('academy_id', $academy->id)->first();
        $response = $this->actingAs($owner, 'api')
            ->putJson("/api/academies/{$academy->id}/subjects/{$subject->id}", [
                'name_th' => 'คณิตศาสตร์ประยุกต์',
                'subject_type' => 'required',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name_th' => 'คณิตศาสตร์ประยุกต์',
        ]);

        // Delete
        $response = $this->actingAs($owner, 'api')
            ->deleteJson("/api/academies/{$academy->id}/subjects/{$subject->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'is_active' => false,
        ]);
    }

    public function test_plain_member_can_list_but_cannot_create_subject(): void
    {
        [$academy, $member] = $this->academyWithMember([]);

        $response = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/subjects");

        $response->assertStatus(200);

        $response = $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/subjects", [
                'name_th' => 'x',
                'subject_type' => 'required',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_create_academic_year_with_semesters(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/academic-years", [
                'name' => '2569',
                'start_date' => '2026-05-01',
                'end_date' => '2027-03-31',
                'create_semesters' => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('academic_years', [
            'academy_id' => $academy->id,
            'name' => '2569',
        ]);

        $year = AcademicYear::where('academy_id', $academy->id)->first();
        $semesterCount = DB::table('semesters')->where('academic_year_id', $year->id)->count();
        $this->assertTrue($semesterCount > 0, 'Semesters should be created');

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/academic-years");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => '2569']);
    }

    public function test_plain_member_cannot_create_academic_year(): void
    {
        [$academy, $member] = $this->academyWithMember([]);

        $response = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/academic-years");

        $response->assertStatus(200);

        $response = $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/academic-years", [
                'name' => '2569',
                'start_date' => '2026-05-01',
                'end_date' => '2027-03-31',
            ]);

        $response->assertStatus(403);
    }
}
