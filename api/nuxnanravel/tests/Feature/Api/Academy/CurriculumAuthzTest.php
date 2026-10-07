<?php

namespace Tests\Feature\Api\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #13 หลักสูตร — CR-S2
 * ปิดช่องโหว่ P0 (G1/G2): authz + tenant isolation ของ CurriculumController
 *
 * Q1: permission key = courses.view / courses.manage
 * Q2: owner/admin + ผู้ถือ courses.manage จัดการได้ · สมาชิกทุกคน (ครู/นักเรียน) ดูได้ทั้งหมด
 * Q3: enroll นักเรียนอยู่ในเมนูนี้ (ใช้สิทธิ์ courses.manage เหมือนการแก้)
 */
class CurriculumAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$academy, $owner];
    }

    /** สมาชิกที่อนุมัติของโรงเรียน พร้อม permission ที่ระบุ (ว่าง = สมาชิกธรรมดา) */
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
            'status' => 2,
        ]);

        return $user;
    }

    private function curriculumIn(Academy $academy): Curriculum
    {
        return Curriculum::create([
            'academy_id' => $academy->id,
            'name' => 'หลักสูตรทดสอบ',
            'is_active' => true,
        ]);
    }

    // ---- create (manage) ----

    public function test_academy_owner_can_create_curriculum(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/curriculums", ['name' => 'วิทย์-คณิต'])
            ->assertStatus(201);

        $this->assertDatabaseHas('curriculums', ['academy_id' => $academy->id, 'name' => 'วิทย์-คณิต']);
    }

    public function test_member_with_courses_manage_can_create_curriculum(): void
    {
        [$academy] = $this->academyOwnedBy();
        $academic = $this->memberOf($academy, ['courses.manage']);

        $this->actingAs($academic, 'api')
            ->postJson("/api/academies/{$academy->id}/curriculums", ['name' => 'ศิลป์-ภาษา'])
            ->assertStatus(201);
    }

    public function test_plain_member_cannot_create_curriculum(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy); // ไม่มี courses.manage

        $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/curriculums", ['name' => 'ห้ามสร้าง'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('curriculums', ['name' => 'ห้ามสร้าง']);
    }

    // ---- view (any member) ----

    public function test_member_can_view_curriculums(): void
    {
        [$academy] = $this->academyOwnedBy();
        $this->curriculumIn($academy);
        $member = $this->memberOf($academy); // สมาชิกธรรมดา (เช่น นักเรียน) ดูได้ตาม Q2

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/curriculums")
            ->assertStatus(200);
    }

    public function test_non_member_cannot_view_curriculums(): void
    {
        [$academy] = $this->academyOwnedBy();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/curriculums")
            ->assertStatus(403);
    }

    // ---- update (manage) ----

    public function test_admin_can_update_curriculum(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $curriculum = $this->curriculumIn($academy);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/curriculums/{$curriculum->id}", ['name' => 'แก้ชื่อหลักสูตร'])
            ->assertStatus(200);

        $this->assertDatabaseHas('curriculums', ['id' => $curriculum->id, 'name' => 'แก้ชื่อหลักสูตร']);
    }

    public function test_plain_member_cannot_update_curriculum(): void
    {
        [$academy] = $this->academyOwnedBy();
        $curriculum = $this->curriculumIn($academy);
        $member = $this->memberOf($academy);

        $this->actingAs($member, 'api')
            ->patchJson("/api/academies/curriculums/{$curriculum->id}", ['name' => 'ห้ามแก้'])
            ->assertStatus(403);
    }

    // ---- tenant isolation (G2) ----

    public function test_admin_of_another_academy_cannot_update_curriculum(): void
    {
        [$academyA] = $this->academyOwnedBy();
        $curriculum = $this->curriculumIn($academyA);
        [, $ownerB] = $this->academyOwnedBy(); // เจ้าของอีกโรงเรียน

        $this->actingAs($ownerB, 'api')
            ->patchJson("/api/academies/curriculums/{$curriculum->id}", ['name' => 'ข้ามโรงเรียน'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('curriculums', ['id' => $curriculum->id, 'name' => 'ข้ามโรงเรียน']);
    }

    public function test_admin_of_another_academy_cannot_read_curriculum_students(): void
    {
        [$academyA] = $this->academyOwnedBy();
        $curriculum = $this->curriculumIn($academyA);
        [, $ownerB] = $this->academyOwnedBy();

        // getStudents เคยรั่ว name/email/photo ข้ามโรงเรียน (G2)
        $this->actingAs($ownerB, 'api')
            ->getJson("/api/academies/curriculums/{$curriculum->id}/students")
            ->assertStatus(403);
    }
}
