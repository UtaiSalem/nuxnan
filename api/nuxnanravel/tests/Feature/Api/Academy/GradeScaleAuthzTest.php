<?php

namespace Tests\Feature\Api\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GR-S1/G1 + GR-S3/G3+G4 — ด่านสิทธิ์ของ grade-scales (เมนู #19 ผลการเรียน)
 *
 * ยืนยัน: อ่าน=grades.view · เขียน=grades.manage ที่ route · custom role ใช้ได้
 * (authz ใช้ userCan ไม่ใช่ role literal อีกต่อไป)
 */
class GradeScaleAuthzTest extends TestCase
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

    private function scalePayload(): array
    {
        return [
            'name' => 'มาตรฐาน 8 ระดับ',
            'items' => [
                ['grade' => '4', 'min_score' => 80, 'max_score' => 100, 'grade_points' => 4],
                ['grade' => '0', 'min_score' => 0, 'max_score' => 49, 'grade_points' => 0],
            ],
        ];
    }

    public function test_non_member_cannot_read_grade_scales(): void
    {
        [$academy] = $this->academyOwnedBy();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->getJson("/api/academies/{$academy->id}/grade-scales")
            ->assertForbidden();
    }

    public function test_member_with_grades_view_can_read_but_not_create(): void
    {
        [$academy] = $this->academyOwnedBy();
        $viewer = $this->memberOf($academy, ['grades.view']);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$academy->id}/grade-scales")
            ->assertOk();

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->scalePayload())
            ->assertForbidden();
    }

    public function test_custom_role_with_grades_manage_can_create(): void
    {
        [$academy] = $this->academyOwnedBy();
        $manager = $this->memberOf($academy, ['grades.view', 'grades.manage']);

        $this->actingAs($manager, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->scalePayload())
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('grade_scales', [
            'academy_id' => $academy->id,
            'name' => 'มาตรฐาน 8 ระดับ',
        ]);
    }

    public function test_owner_can_create(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/grade-scales", $this->scalePayload())
            ->assertCreated();
    }
}
