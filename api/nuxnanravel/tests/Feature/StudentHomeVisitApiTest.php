<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentHomeVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHomeVisitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_visits_without_staff_observations(): void
    {
        [$owner, $admin, $academy, $student] = $this->context();
        StudentHomeVisit::create($this->visitData($academy->id, $student->id, $admin->id));

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.observations', null);
    }

    public function test_admin_can_create_update_and_delete_visit(): void
    {
        [, $admin, $academy, $student] = $this->context();

        $create = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visit_time' => '09:30',
                'visitor_name' => 'Teacher One',
                'visitor_position' => 'Teacher',
                'visit_status' => 'completed',
                'observations' => 'Safe home environment',
            ]);

        $create->assertCreated()->assertJsonPath('data.visit_status', 'completed');
        $visitId = $create->json('data.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'Teacher One',
                'visitor_position' => 'Homeroom teacher',
                'visit_status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('data.visit_status', 'cancelled');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits/{$visitId}")
            ->assertOk();

        $this->assertDatabaseMissing('student_home_visits', ['id' => $visitId]);
    }

    public function test_non_member_cannot_view_or_create_visits(): void
    {
        [, , $academy, $student] = $this->context();
        $stranger = $this->makeUser('stranger');

        $this->actingAs($stranger, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits")
            ->assertForbidden();

        $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/home-visits", [
                'visit_date' => '2026-07-03',
                'visitor_name' => 'No Access',
                'visitor_position' => 'Unknown',
                'visit_status' => 'completed',
            ])
            ->assertForbidden();
    }

    private function context(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('admin');
        $academy = Academy::create(['name' => 'Academy_'.uniqid(), 'user_id' => $admin->id]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 1,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        return [$owner, $admin, $academy, $student];
    }

    private function makeUser(string $tag): User
    {
        return User::create([
            'name' => ucfirst($tag),
            'email' => $tag.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'username' => $tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function visitData(int $academyId, int $studentId, int $creatorId): array
    {
        return [
            'academy_id' => $academyId,
            'student_id' => $studentId,
            'visit_date' => '2026-07-03',
            'visitor_name' => 'Teacher One',
            'visitor_position' => 'Teacher',
            'visit_status' => 'completed',
            'observations' => 'Private observation',
            'created_by' => $creatorId,
        ];
    }
}
