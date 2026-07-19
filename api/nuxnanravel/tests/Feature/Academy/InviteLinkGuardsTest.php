<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyInviteLink;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteLinkGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $admin = User::factory()->create();
        $student = User::factory()->create();
        $adminRole = AcademyRole::where('name', 'admin')->whereNull('academy_id')->firstOrFail();
        $studentRole = AcademyRole::where('name', 'student')->whereNull('academy_id')->firstOrFail();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'academy_role_id' => $adminRole->id, 'role' => 'admin', 'status' => 2]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $student->id, 'academy_role_id' => $studentRole->id, 'role' => 'student', 'status' => 2]);

        return [$owner, $academy, $admin, $student];
    }

    public function test_owner_can_list(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/invite-links")->assertOk();
    }

    public function test_admin_can_create(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/invite-links", ['name' => 'Test'])->assertCreated();
    }

    public function test_student_cannot_list(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $this->actingAs($student, 'api')->getJson("/api/academies/{$academy->id}/invite-links")->assertForbidden();
    }

    public function test_student_cannot_create(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/invite-links", ['name' => 'Test'])->assertForbidden();
    }

    public function test_admin_can_delete(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $link = AcademyInviteLink::create(['academy_id' => $academy->id, 'created_by' => $admin->id, 'name' => 'Delete']);
        $this->actingAs($admin, 'api')->deleteJson("/api/academies/{$academy->id}/invite-links/{$link->id}")->assertOk();
    }

    public function test_student_cannot_delete(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $link = AcademyInviteLink::create(['academy_id' => $academy->id, 'created_by' => $student->id, 'name' => 'Delete']);
        $this->actingAs($student, 'api')->deleteJson("/api/academies/{$academy->id}/invite-links/{$link->id}")->assertForbidden();
    }

    public function test_admin_can_toggle_active(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $link = AcademyInviteLink::create(['academy_id' => $academy->id, 'created_by' => $admin->id, 'name' => 'Toggle']);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/invite-links/{$link->id}/toggle-active")->assertOk();
    }
}
