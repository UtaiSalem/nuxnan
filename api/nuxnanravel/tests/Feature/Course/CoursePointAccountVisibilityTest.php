<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePointAccountVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_sees_only_public_fund_fields()
    {
        $course = Course::factory()->create();
        $account = CoursePointAccount::create([
            'course_id' => $course->id,
            'balance' => 5000,
            'total_distributed' => 1200,
            'total_earned' => 9000,
            'total_withdrawn' => 800,
            'reserved_balance' => 300,
            'minimum_withdrawal' => 24000,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson("/api/courses/{$course->id}/points/account");

        $response->assertStatus(200);
        $response->assertJsonPath('data.balance', 5000);
        $response->assertJsonPath('data.total_distributed', 1200);

        $response->assertJsonMissingPath('data.available_balance');
        $response->assertJsonMissingPath('data.reserved_balance');
        $response->assertJsonMissingPath('data.total_earned');
        $response->assertJsonMissingPath('data.total_withdrawn');
        $response->assertJsonMissingPath('data.minimum_withdrawal');
        $response->assertJsonMissingPath('data.commission_rate');
        $response->assertJsonMissingPath('data.platform_earned');
    }

    public function test_course_admin_still_sees_every_field()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $account = CoursePointAccount::create([
            'course_id' => $course->id,
            'balance' => 5000,
            'total_distributed' => 1200,
            'total_earned' => 9000,
            'total_withdrawn' => 800,
            'reserved_balance' => 300,
            'minimum_withdrawal' => 24000,
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/courses/{$course->id}/points/account");

        $response->assertStatus(200);
        $response->assertJsonPath('data.balance', 5000);
        $response->assertJsonPath('data.total_earned', 9000);
        $response->assertJsonPath('data.total_withdrawn', 800);
        $response->assertJsonPath('data.total_distributed', 1200);
        $response->assertJsonPath('data.available_balance', 4700);
        $response->assertJsonPath('data.minimum_withdrawal', 24000);
    }

    public function test_non_admin_still_blocked_from_transactions_and_withdraw()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson("/api/courses/{$course->id}/points/transactions");
        $response->assertStatus(403);

        $response = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/points/withdraw", [
            'amount' => 100,
        ]);
        $response->assertStatus(403);
    }

    public function test_account_endpoint_returns_zeros_when_no_account_row_exists()
    {
        $course = Course::factory()->create();
        $nonAdmin = User::factory()->create();

        $response = $this->actingAs($nonAdmin, 'api')->getJson("/api/courses/{$course->id}/points/account");
        $response->assertStatus(200);
        $response->assertJsonPath('data.balance', 0);
        $response->assertJsonPath('data.total_distributed', 0);

        $admin = User::factory()->create();
        $courseAdmin = Course::factory()->create(['user_id' => $admin->id]);

        $responseAdmin = $this->actingAs($admin, 'api')->getJson("/api/courses/{$courseAdmin->id}/points/account");
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJsonPath('data.minimum_withdrawal', 24000);
    }
}
