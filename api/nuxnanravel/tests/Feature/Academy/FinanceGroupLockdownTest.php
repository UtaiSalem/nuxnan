<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceGroupLockdownTest extends TestCase
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

    public function test_finance_view_holder_is_now_blocked(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.view']);

        $response = $this->actingAs($finance, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures/fee-types");

        $response->assertStatus(403);
    }

    public function test_plain_member_is_blocked(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy);

        $response = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures/fee-types");

        $response->assertStatus(403);
    }

    public function test_settings_manage_holder_passes_guard(): void
    {
        [$academy] = $this->academyOwnedBy();
        $settingsManager = $this->memberOf($academy, ['settings.manage']);

        $response = $this->actingAs($settingsManager, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures/fee-types");

        $this->assertNotSame(403, $response->status());
    }

    public function test_academy_admin_passes_guard(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures/fee-types");

        $this->assertNotSame(403, $response->status());
    }
}
