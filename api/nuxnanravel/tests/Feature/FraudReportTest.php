<?php

namespace Tests\Feature;

use App\Models\AccountFraudReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reporter;

    private User $accused;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        Role::firstOrCreate(['name' => 'STUDENT']);

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('SUPER_ADMIN');

        $this->reporter = User::factory()->create(['email' => 'reporter@test.com']);
        $this->accused = User::factory()->create(['email' => 'accused@test.com']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'reported_user_id' => $this->accused->id,
            'category' => 'money_fraud',
            'description' => 'บัญชีนี้หลอกโอนเงินแล้วไม่ส่งของ',
        ], $overrides);
    }

    /** @test */
    public function member_can_submit_a_fraud_report(): void
    {
        $response = $this->actingAs($this->reporter, 'api')
            ->postJson('/api/fraud-reports', $this->validPayload([
                'related_transaction_type' => 'wallet',
                'related_transaction_id' => 123,
                'evidence_note' => 'สลิปโอนเงินแนบในแชท',
            ]));

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('account_fraud_reports', [
            'reporter_id' => $this->reporter->id,
            'reported_user_id' => $this->accused->id,
            'category' => 'money_fraud',
            'status' => AccountFraudReport::STATUS_PENDING,
            'related_transaction_type' => 'wallet',
            'related_transaction_id' => 123,
        ]);
    }

    /** @test */
    public function member_cannot_report_their_own_account(): void
    {
        $response = $this->actingAs($this->reporter, 'api')
            ->postJson('/api/fraud-reports', $this->validPayload([
                'reported_user_id' => $this->reporter->id,
            ]));

        $response->assertStatus(422);
        $this->assertDatabaseCount('account_fraud_reports', 0);
    }

    /** @test */
    public function duplicate_open_report_is_blocked(): void
    {
        $this->actingAs($this->reporter, 'api')
            ->postJson('/api/fraud-reports', $this->validPayload())
            ->assertStatus(201);

        $this->actingAs($this->reporter, 'api')
            ->postJson('/api/fraud-reports', $this->validPayload())
            ->assertStatus(422);

        $this->assertDatabaseCount('account_fraud_reports', 1);
    }

    /** @test */
    public function description_is_required_and_has_minimum_length(): void
    {
        $this->actingAs($this->reporter, 'api')
            ->postJson('/api/fraud-reports', $this->validPayload(['description' => 'สั้น']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');
    }

    /** @test */
    public function member_can_list_their_own_reports(): void
    {
        AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));
        // A report by another reporter should not appear.
        AccountFraudReport::create([
            'reporter_id' => $this->accused->id,
            'reported_user_id' => $this->reporter->id,
            'category' => 'scam',
            'description' => 'รายงานโต้กลับ ไม่ควรเห็นในรายการของ reporter',
        ]);

        $response = $this->actingAs($this->reporter, 'api')->getJson('/api/fraud-reports/mine');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(1, $response->json('data.data'));
    }

    /** @test */
    public function admin_can_list_and_filter_reports(): void
    {
        AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));
        AccountFraudReport::create($this->validPayload([
            'reporter_id' => $this->reporter->id,
            'reported_user_id' => $this->admin->id,
            'status' => AccountFraudReport::STATUS_DISMISSED,
        ]));

        $all = $this->actingAs($this->admin, 'api')->getJson('/api/admin/fraud-reports');
        $all->assertStatus(200);
        $this->assertCount(2, $all->json('data.data'));

        $pending = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/fraud-reports?status=pending');
        $pending->assertStatus(200);
        $this->assertCount(1, $pending->json('data.data'));
    }

    /** @test */
    public function admin_can_see_status_stats(): void
    {
        AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/fraud-reports/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.total', 1);
    }

    /** @test */
    public function admin_can_update_report_status(): void
    {
        $report = AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/fraud-reports/{$report->id}/status", [
                'status' => AccountFraudReport::STATUS_DISMISSED,
                'admin_note' => 'หลักฐานไม่เพียงพอ',
            ])
            ->assertStatus(200);

        $report->refresh();
        $this->assertSame(AccountFraudReport::STATUS_DISMISSED, $report->status);
        $this->assertSame($this->admin->id, $report->handled_by);
        $this->assertNotNull($report->resolved_at);
    }

    /** @test */
    public function admin_can_suspend_reported_account_from_report(): void
    {
        $report = AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/fraud-reports/{$report->id}/suspend", [
                'points' => true,
                'wallet' => true,
                'reason' => 'ยืนยันทุจริตจากหลักฐาน',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $this->accused->id,
            'points_suspended' => true,
            'wallet_suspended' => true,
        ]);

        $this->assertDatabaseHas('account_suspension_audits', [
            'user_id' => $this->accused->id,
            'action' => 'suspend',
            'performed_by' => $this->admin->id,
        ]);

        $report->refresh();
        $this->assertSame(AccountFraudReport::STATUS_ACTION_TAKEN, $report->status);
        $this->assertNotNull($report->resolved_at);
    }

    /** @test */
    public function suspend_requires_at_least_one_system(): void
    {
        $report = AccountFraudReport::create($this->validPayload(['reporter_id' => $this->reporter->id]));

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/fraud-reports/{$report->id}/suspend", [
                'points' => false,
                'wallet' => false,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $this->accused->id,
            'points_suspended' => false,
            'wallet_suspended' => false,
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_admin_report_queue(): void
    {
        $this->reporter->assignRole('STUDENT');

        $this->actingAs($this->reporter, 'api')
            ->getJson('/api/admin/fraud-reports')
            ->assertStatus(403);
    }
}
