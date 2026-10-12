<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SchoolAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #20 ประกาศ — พิสูจน์ว่าสิทธิ์จัดการตัดสินที่คีย์ announcements.manage
 * (ผ่าน Academy::userCan) ไม่ใช่ isAcademyAdmin/member.role แบบ hardcode (G1)
 * ดู .agents/school-admin/20-announcements.md
 */
class AnnouncementAuthzTest extends TestCase
{
    use RefreshDatabase;

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

    private function payload(): array
    {
        return [
            'title' => 'ประกาศทดสอบ',
            'content' => 'เนื้อหาประกาศทดสอบ',
            'announcement_type' => 'general',
            'priority' => 'normal',
        ];
    }

    // ---- G1: custom role ที่ถือ announcements.manage สร้างได้ (เดิมโดน isAcademyAdmin บล็อก) ----

    public function test_custom_role_with_manage_can_create_announcement(): void
    {
        [$academy, $user] = $this->academyWithMember(['announcements.view', 'announcements.manage']);

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", $this->payload())
            ->assertStatus(201);

        $this->assertDatabaseHas('school_announcements', [
            'academy_id' => $academy->id,
            'title' => 'ประกาศทดสอบ',
            'created_by' => $user->id,
        ]);
    }

    public function test_custom_role_with_manage_can_view_stats(): void
    {
        [$academy, $user] = $this->academyWithMember(['announcements.view', 'announcements.manage']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements/stats")
            ->assertStatus(200);
    }

    // ---- ผู้ถือแค่ view: อ่านรายการได้ แต่สร้าง/ดูสถิติไม่ได้ ----

    public function test_view_only_member_can_list_but_cannot_create(): void
    {
        [$academy, $user] = $this->academyWithMember(['announcements.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements")
            ->assertStatus(200);

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", $this->payload())
            ->assertStatus(403);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements/stats")
            ->assertStatus(403);
    }

    // ---- owner ผ่านเสมอ (isAdmin short-circuit ใน userCan) ----

    public function test_owner_can_create_announcement(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", $this->payload())
            ->assertStatus(201);
    }

    // ---- custom role manage แก้/ลบของโรงเรียนตนได้ · ข้ามโรงเรียนไม่ได้ ----

    public function test_manage_role_can_delete_own_academy_announcement(): void
    {
        [$academy, $user] = $this->academyWithMember(['announcements.view', 'announcements.manage']);
        $announcement = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'academy',
            'scope_id' => $academy->id,
            'created_by' => $user->id,
            'title' => 'ลบฉัน',
            'content' => 'x',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => false,
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson("/api/academies/{$academy->id}/announcements/{$announcement->id}")
            ->assertStatus(200);
    }

    public function test_manage_role_cannot_create_in_other_academy(): void
    {
        [, $outsider] = $this->academyWithMember(['announcements.view', 'announcements.manage']);
        [$target] = $this->academyWithMember(['announcements.view']);

        $this->actingAs($outsider, 'api')
            ->postJson("/api/academies/{$target->id}/announcements", $this->payload())
            ->assertStatus(403);
    }
}
