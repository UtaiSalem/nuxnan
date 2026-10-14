<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\SchoolAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AN-S1 — ด่านสิทธิ์ประกาศ (เมนู #20)
 *
 * G1: controller ใช้ userCan('announcements.manage') ให้ตรงกับ route
 *     (เดิมเช็ค isAdmin ล้วน ⇒ director/custom role ที่ถือคีย์โดน 403)
 * G3: scope_id ต้องอยู่ในโรงเรียนนี้
 */
class AnnouncementAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$academy, $owner];
    }

    private function memberOf(Academy $academy, array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'บทบาททดสอบ',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => AcademyMember::STATUS_APPROVED,
        ]);

        return $user;
    }

    public function test_custom_role_with_announcements_manage_can_create(): void
    {
        [$academy] = $this->academyOwnedBy();
        $manager = $this->memberOf($academy, ['announcements.view', 'announcements.manage']);

        $this->actingAs($manager, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", [
                'title' => 'ประกาศปิดเรียน',
                'content' => 'ปิดเรียนวันศุกร์',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('school_announcements', [
            'academy_id' => $academy->id,
            'title' => 'ประกาศปิดเรียน',
        ]);
    }

    public function test_member_without_manage_cannot_create(): void
    {
        [$academy] = $this->academyOwnedBy();
        $viewer = $this->memberOf($academy, ['announcements.view']);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", [
                'title' => 'x', 'content' => 'y',
            ])
            ->assertForbidden();
    }

    public function test_non_member_cannot_read_or_create(): void
    {
        [$academy] = $this->academyOwnedBy();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements")
            ->assertForbidden();

        $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", ['title' => 'x', 'content' => 'y'])
            ->assertForbidden();
    }

    public function test_show_rejects_cross_academy_announcement(): void
    {
        [$academyA, $ownerA] = $this->academyOwnedBy();
        [$academyB] = $this->academyOwnedBy();

        $annB = SchoolAnnouncement::create([
            'academy_id' => $academyB->id,
            'scope_type' => 'academy',
            'scope_id' => $academyB->id,
            'created_by' => $ownerA->id,
            'title' => 'ของโรงเรียน B',
            'content' => '...',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // owner ของ A เปิดประกาศของ B ผ่าน path ของ A → 404 (tenant)
        $this->actingAs($ownerA, 'api')
            ->getJson("/api/academies/{$academyA->id}/announcements/{$annB->id}")
            ->assertNotFound();
    }

    public function test_store_rejects_scope_id_not_in_academy(): void
    {
        [$academyA, $ownerA] = $this->academyOwnedBy();

        // classroom scope ที่ชี้ id ซึ่งไม่อยู่ในโรงเรียนนี้ (ไม่มีจริง/ข้ามโรงเรียน) → 422
        $this->actingAs($ownerA, 'api')
            ->postJson("/api/academies/{$academyA->id}/announcements", [
                'title' => 'ประกาศห้อง',
                'content' => '...',
                'scope_type' => 'classroom',
                'scope_id' => 999999,
            ])
            ->assertStatus(422);
    }

    // ===== AN-S2/G2 — ครู create.own =====

    private function classroomWithHomeroom(Academy $academy, User $teacher): Classroom
    {
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        return Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_teacher_create_own_can_create_for_own_classroom(): void
    {
        [$academy] = $this->academyOwnedBy();
        $teacher = $this->memberOf($academy, ['announcements.view', 'announcements.create.own']);
        $classroom = $this->classroomWithHomeroom($academy, $teacher);

        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", [
                'title' => 'ประกาศห้อง ม.1/1',
                'content' => 'พรุ่งนี้มาสาย',
                'scope_type' => 'classroom',
                'scope_id' => $classroom->id,
            ])
            ->assertCreated();
    }

    public function test_teacher_create_own_cannot_create_academy_scope(): void
    {
        [$academy] = $this->academyOwnedBy();
        $teacher = $this->memberOf($academy, ['announcements.view', 'announcements.create.own']);
        $this->classroomWithHomeroom($academy, $teacher);

        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", [
                'title' => 'ประกาศทั้งโรงเรียน',
                'content' => '...',
                'scope_type' => 'academy',
            ])
            ->assertForbidden();
    }

    public function test_teacher_create_own_cannot_create_for_other_classroom(): void
    {
        [$academy] = $this->academyOwnedBy();
        $teacher = $this->memberOf($academy, ['announcements.view', 'announcements.create.own']);
        $otherTeacher = $this->memberOf($academy, ['announcements.view', 'announcements.create.own']);
        $otherClassroom = $this->classroomWithHomeroom($academy, $otherTeacher);

        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/announcements", [
                'title' => 'แอบประกาศห้องคนอื่น',
                'content' => '...',
                'scope_type' => 'classroom',
                'scope_id' => $otherClassroom->id,
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_modify_own_but_not_others_announcement(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $teacher = $this->memberOf($academy, ['announcements.view', 'announcements.create.own']);
        $classroom = $this->classroomWithHomeroom($academy, $teacher);

        // ประกาศห้องของครูเอง
        $own = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'classroom',
            'scope_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'ของครูเอง',
            'content' => '...',
        ]);
        // ประกาศระดับโรงเรียนที่ owner สร้าง
        $others = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'academy',
            'scope_id' => $academy->id,
            'created_by' => $owner->id,
            'title' => 'ของผู้บริหาร',
            'content' => '...',
        ]);

        $this->actingAs($teacher, 'api')
            ->patchJson("/api/academies/{$academy->id}/announcements/{$own->id}", ['title' => 'แก้ของตัวเอง'])
            ->assertOk();

        $this->actingAs($teacher, 'api')
            ->deleteJson("/api/academies/{$academy->id}/announcements/{$others->id}")
            ->assertForbidden();
    }
}
