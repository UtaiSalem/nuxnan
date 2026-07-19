<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTagGuardsTest extends TestCase
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

    public function test_admin_can_list_tags(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $this->actingAs($admin, 'api')->getJson("/api/academies/{$academy->id}/member-tags")->assertOk();
    }

    public function test_admin_can_create_tag(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/member-tags", ['name' => 'Test'])->assertCreated();
    }

    public function test_student_can_list_tags(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $this->actingAs($student, 'api')->getJson("/api/academies/{$academy->id}/member-tags")->assertOk();
    }

    public function test_student_cannot_create_tag(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/member-tags", ['name' => 'Test'])->assertForbidden();
    }

    public function test_admin_can_delete_tag(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Test Tag', 'color' => '#6366f1']);
        $this->actingAs($admin, 'api')->deleteJson("/api/academies/{$academy->id}/member-tags/{$tag->id}")->assertOk();
    }

    public function test_student_cannot_delete_tag(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Test Tag', 'color' => '#6366f1']);
        $this->actingAs($student, 'api')->deleteJson("/api/academies/{$academy->id}/member-tags/{$tag->id}")->assertForbidden();
    }

    public function test_admin_can_bulk_add_tag(): void
    {
        [, $academy, $admin] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Test Tag', 'color' => '#6366f1']);
        $member = AcademyMember::where('academy_id', $academy->id)->first();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/member-tags/bulk-add", ['member_ids' => [$member->id], 'tag_id' => $tag->id])->assertOk();
    }

    public function test_student_cannot_bulk_add_tag(): void
    {
        [, $academy, , $student] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Test Tag', 'color' => '#6366f1']);
        $member = AcademyMember::where('academy_id', $academy->id)->first();
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/member-tags/bulk-add", ['member_ids' => [$member->id], 'tag_id' => $tag->id])->assertForbidden();
    }
}
