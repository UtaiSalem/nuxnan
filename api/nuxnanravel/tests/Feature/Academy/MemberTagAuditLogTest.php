<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\MemberTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTagAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::where('name', 'admin')->whereNull('academy_id')->firstOrFail();
        $member = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'academy_role_id' => $role->id, 'role' => 'admin', 'status' => 2]);

        return [$owner, $academy, $member];
    }

    public function test_create_logs_tag_create(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/member-tags", ['name' => 'Created'])->assertCreated();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_TAG_CREATE]);
    }

    public function test_delete_logs_tag_delete(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Deleted', 'color' => '#6366f1']);
        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/member-tags/{$tag->id}")->assertOk();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_TAG_DELETE]);
    }

    public function test_bulk_add_logs_tag_assign(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $tag = MemberTag::create(['academy_id' => $academy->id, 'name' => 'Assigned', 'color' => '#6366f1']);
        $member = AcademyMember::where('academy_id', $academy->id)->firstOrFail();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/member-tags/bulk-add", ['member_ids' => [$member->id], 'tag_id' => $tag->id])->assertOk();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_TAG_ASSIGN]);
    }
}
