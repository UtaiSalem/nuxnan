<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #19 ผลการเรียน — พิสูจน์ว่าด่านสิทธิ์ตัดสินที่คีย์ grades.* (ผ่าน Academy::userCan)
 * ไม่ใช่ member.role แบบ hardcode อีกต่อไป (G1) + ปิด read ของ grade-scales (G3)
 * ดู .agents/school-admin/19-grades.md
 */
class GradesAuthzTest extends TestCase
{
    use RefreshDatabase;

    /** สมาชิกของโรงเรียนที่ถือ custom role พร้อมชุดคีย์ที่กำหนด */
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

    private function sampleScalePayload(): array
    {
        return [
            'name' => 'มาตราส่วน 8 ระดับ',
            'is_default' => false,
            'items' => [
                ['grade' => '4', 'letter_grade' => 'A', 'min_score' => 80, 'max_score' => 100, 'grade_points' => 4],
                ['grade' => '0', 'letter_grade' => 'F', 'min_score' => 0, 'max_score' => 49, 'grade_points' => 0],
            ],
        ];
    }

    // ---- G1: custom role ที่ถือ grades.manage จัดการได้ (เดิมถูกบล็อกเพราะไม่ใช่ owner/director/admin) ----

    public function test_custom_role_with_grades_manage_can_create_grade_scale(): void
    {
        [$academy, $user] = $this->academyWithMember(['grades.view', 'grades.manage']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->sampleScalePayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('grade_scales', [
            'academy_id' => $academy->id,
            'name' => 'มาตราส่วน 8 ระดับ',
        ]);
    }

    public function test_custom_role_with_grades_manage_can_create_academic_year(): void
    {
        [$academy, $user] = $this->academyWithMember(['grades.view', 'grades.manage']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/academic-years", [
                'name' => '2569',
                'start_date' => '2026-05-01',
                'end_date' => '2027-03-31',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('academic_years', [
            'academy_id' => $academy->id,
            'name' => '2569',
        ]);
    }

    // ---- Q2: academic-years ยังรับ courses.manage ได้ด้วย ----

    public function test_member_with_courses_manage_can_create_academic_year(): void
    {
        [$academy, $user] = $this->academyWithMember(['courses.manage']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/academic-years", [
                'name' => '2570',
                'start_date' => '2027-05-01',
                'end_date' => '2028-03-31',
            ]);

        $response->assertStatus(201);
    }

    // ---- Q4: grades.view อ่านได้แต่จัดการไม่ได้ ----

    public function test_grades_view_can_read_but_cannot_manage_grade_scale(): void
    {
        [$academy, $user] = $this->academyWithMember(['grades.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/grade-scales")
            ->assertStatus(200);

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->sampleScalePayload())
            ->assertStatus(403);
    }

    // ---- G3: สมาชิกที่ไม่มี grades.view อ่าน grade-scales ไม่ได้ (เดิมเปิดโล่ง) ----

    public function test_plain_member_cannot_read_grade_scales(): void
    {
        [$academy, $user] = $this->academyWithMember([]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/grade-scales")
            ->assertStatus(403);
    }

    // ---- G3: ข้ามโรงเรียนไม่ได้ (เดิม index/show ไม่มีด่านเลย) ----

    public function test_member_of_other_academy_cannot_read_grade_scales(): void
    {
        [, $outsider] = $this->academyWithMember(['grades.view', 'grades.manage']);
        [$target] = $this->academyWithMember(['grades.view']);

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$target->id}/grade-scales")
            ->assertStatus(403);
    }

    // ---- Q3: ครู/ผู้ถือ grades.view เห็นภาพรวม transcript ของทั้งโรงเรียน ----

    public function test_grades_view_can_access_transcript_overview(): void
    {
        [$academy, $user] = $this->academyWithMember(['grades.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/transcripts/overview")
            ->assertStatus(200);
    }

    public function test_plain_member_cannot_access_transcript_overview(): void
    {
        [$academy, $user] = $this->academyWithMember([]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/transcripts/overview")
            ->assertStatus(403);
    }

    // ---- owner (academy.user_id) ผ่านเสมอผ่าน isAdmin short-circuit ----

    public function test_owner_can_manage_grade_scale(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->sampleScalePayload())
            ->assertStatus(201);
    }
}
