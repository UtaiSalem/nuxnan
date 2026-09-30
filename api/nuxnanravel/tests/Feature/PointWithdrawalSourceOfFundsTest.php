<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\PlearndAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moderator fraud-review control for Academy/Course point withdrawals: trace
 * who funded the point account before approving a payout. The point-side tell
 * is the requester funding their own account (self-funding) or one account
 * supplying most of what is being withdrawn.
 */
class PointWithdrawalSourceOfFundsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        PlearndAdmin::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_course_source_of_funds_flags_owner_self_funding(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'name' => 'เจ้าของคอร์ส']);
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => 100000, 'reserved_balance' => 0]);

        // Owner funds their own course account, then a student pays lesson points.
        CoursePointTransaction::create([
            'course_point_account_id' => $account->id, 'course_id' => $course->id, 'user_id' => $owner->id,
            'type' => 'donation_point_credit', 'amount' => 50000, 'balance_before' => 0, 'balance_after' => 50000,
        ]);
        $student = User::factory()->create();
        CoursePointTransaction::create([
            'course_point_account_id' => $account->id, 'course_id' => $course->id, 'user_id' => $student->id,
            'type' => 'lesson_income', 'amount' => 10000, 'balance_before' => 50000, 'balance_after' => 60000,
        ]);

        $id = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/withdrawals", ['amount' => 24000, 'purpose' => 'x'])
            ->json('data.id');

        $res = $this->actingAs($this->admin(), 'api')
            ->getJson("/api/plearnd-admin/course-withdrawals/{$id}/source-of-funds");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scope', 'course')
            ->assertJsonPath('data.summary.user_funded_points', 60000)
            ->assertJsonPath('data.summary.funders.0.user.id', $owner->id)
            ->assertJsonPath('data.summary.funders.0.is_requester', true)
            ->assertJsonPath('data.risk.level', 'high');

        $this->assertContains('self_funded', $res->json('data.risk.flags'));
    }

    public function test_academy_source_of_funds_low_risk_for_system_credits(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $account = AcademyPointAccount::create(['academy_id' => $academy->id, 'balance' => 100000, 'reserved_balance' => 0]);

        // Only platform ad revenue funds the account (no per-user counterparty).
        AcademyPointTransaction::create([
            'academy_point_account_id' => $account->id, 'academy_id' => $academy->id, 'user_id' => null,
            'type' => 'ad_revenue', 'amount' => 40000, 'balance_before' => 0, 'balance_after' => 40000,
        ]);

        $id = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/withdrawals", ['amount' => 24000, 'purpose' => 'x'])
            ->json('data.id');

        $this->actingAs($this->admin(), 'api')
            ->getJson("/api/plearnd-admin/academy-withdrawals/{$id}/source-of-funds")
            ->assertOk()
            ->assertJsonPath('data.scope', 'academy')
            ->assertJsonPath('data.summary.user_funded_points', 0)
            ->assertJsonPath('data.risk.level', 'low');
    }

    public function test_source_of_funds_requires_admin(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $course = Course::factory()->create(['user_id' => $owner->id]);
        CoursePointAccount::create(['course_id' => $course->id, 'balance' => 100000, 'reserved_balance' => 0]);
        $id = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/withdrawals", ['amount' => 24000])
            ->json('data.id');

        // The requesting owner is not a Plearnd admin -> blocked.
        $this->actingAs($owner, 'api')
            ->getJson("/api/plearnd-admin/course-withdrawals/{$id}/source-of-funds")
            ->assertForbidden();
    }
}
