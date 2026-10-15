<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #24 แดชบอร์ด — DB-S10
 * GET /{academy}/dashboard/summary — รวม signal 4 ตัว gate ต่อ signal ด้วย userCan
 * พิสูจน์: owner เห็นครบ · สิทธิ์เดี่ยวเห็นเฉพาะ signal ตน · สมาชิกเปล่าเห็นแค่ active_today
 */
class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create([
            'user_id' => $owner->id,
            'donation_enabled' => true,
        ]);

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
            'status' => 2,
        ]);

        return $user;
    }

    private function pendingDonationIn(Academy $academy): AcademyDonate
    {
        return AcademyDonate::create([
            'academy_id' => $academy->id,
            'donor_id' => User::factory()->create()->id,
            'donation_type' => 'cash',
            'cash_amount' => 100,
            'currency' => 'THB',
            'status' => 'pending',
            'version' => 1,
        ]);
    }

    public function test_owner_admin_sees_all_four_signals_and_counts_donations(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $this->pendingDonationIn($academy);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/dashboard/summary");

        $response->assertStatus(200)->assertJsonPath('success', true);

        $summary = $response->json('summary');
        foreach (['active_today', 'donations_pending', 'attendance_today', 'grades_pending'] as $key) {
            $this->assertArrayHasKey($key, $summary, "owner ควรเห็น $key");
            $this->assertIsInt($summary[$key]);
        }
        $this->assertSame(1, $summary['donations_pending']);
    }

    public function test_finance_view_member_sees_only_donations_and_active(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy, ['finance.view']);

        $summary = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/dashboard/summary")
            ->assertStatus(200)
            ->json('summary');

        $this->assertArrayHasKey('active_today', $summary);
        $this->assertArrayHasKey('donations_pending', $summary);
        $this->assertArrayNotHasKey('attendance_today', $summary);
        $this->assertArrayNotHasKey('grades_pending', $summary);
    }

    public function test_attendance_view_member_sees_only_attendance_and_active(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy, ['school_attendance.view']);

        $summary = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/dashboard/summary")
            ->assertStatus(200)
            ->json('summary');

        $this->assertArrayHasKey('active_today', $summary);
        $this->assertArrayHasKey('attendance_today', $summary);
        $this->assertArrayNotHasKey('donations_pending', $summary);
        $this->assertArrayNotHasKey('grades_pending', $summary);
    }

    public function test_grades_view_member_sees_only_grades_and_active(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy, ['grades.view']);

        $summary = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/dashboard/summary")
            ->assertStatus(200)
            ->json('summary');

        $this->assertArrayHasKey('active_today', $summary);
        $this->assertArrayHasKey('grades_pending', $summary);
        $this->assertArrayNotHasKey('donations_pending', $summary);
        $this->assertArrayNotHasKey('attendance_today', $summary);
    }

    public function test_plain_member_sees_only_active_today(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy, []);

        $summary = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/dashboard/summary")
            ->assertStatus(200)
            ->json('summary');

        $this->assertArrayHasKey('active_today', $summary);
        $this->assertArrayNotHasKey('donations_pending', $summary);
        $this->assertArrayNotHasKey('attendance_today', $summary);
        $this->assertArrayNotHasKey('grades_pending', $summary);
    }
}
