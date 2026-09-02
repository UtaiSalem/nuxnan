<?php

namespace Tests\Feature\Gamification;

use App\Models\PointRule;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\UserUsageEvent;
use App\Services\GamificationRuleEngine;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTimeEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_behavior_remains_same_and_blocks_if_daily_limit_reached()
    {
        $user = User::factory()->create();
        $rule = PointRule::create([
            'rule_key' => 'test_rule', 'rule_name' => 'Test rule', 'action_type' => 'earn',
            'source_type' => 'test_rule', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 0, 'is_active' => true,
            'max_daily_earnings' => 100,
        ]);

        PointsTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'source_type' => 'test_rule',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $service = app(PointsService::class);
        $this->assertFalse($service->canEarnFromRule($user, $rule));
    }

    public function test_past_event_ignores_today_earnings()
    {
        $user = User::factory()->create();
        $rule = PointRule::create([
            'rule_key' => 'test_rule', 'rule_name' => 'Test rule', 'action_type' => 'earn',
            'source_type' => 'test_rule', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 0, 'is_active' => true,
            'max_daily_earnings' => 100,
        ]);

        PointsTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'source_type' => 'test_rule',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $service = app(PointsService::class);
        $past = now()->subMonths(3);
        $this->assertTrue($service->canEarnFromRule($user, $rule, $past));
    }

    public function test_past_event_sees_earnings_of_its_own_day()
    {
        $user = User::factory()->create();
        $rule = PointRule::create([
            'rule_key' => 'test_rule', 'rule_name' => 'Test rule', 'action_type' => 'earn',
            'source_type' => 'test_rule', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 0, 'is_active' => true,
            'max_daily_earnings' => 100,
        ]);

        $past = now()->subMonths(3);

        PointsTransaction::forceCreate([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'source_type' => 'test_rule',
            'status' => 'completed',
            'created_at' => $past,
            'updated_at' => $past,
        ]);

        $service = app(PointsService::class);
        $this->assertFalse($service->canEarnFromRule($user, $rule, $past));
    }

    public function test_cooldown_blocks_if_time_not_passed()
    {
        $user = User::factory()->create();
        $rule = PointRule::create([
            'rule_key' => 'test_rule', 'rule_name' => 'Test rule', 'action_type' => 'earn',
            'source_type' => 'test_rule', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 0, 'is_active' => true,
            'cooldown_minutes' => 60,
        ]);

        $at = now()->subMonths(3);
        $txTime = $at->copy()->subMinutes(30);

        PointsTransaction::forceCreate([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'source_type' => 'test_rule',
            'status' => 'completed',
            'created_at' => $txTime,
            'updated_at' => $txTime,
        ]);

        $service = app(PointsService::class);
        $this->assertFalse($service->canEarnFromRule($user, $rule, $at));
    }

    public function test_cooldown_allows_if_time_passed()
    {
        $user = User::factory()->create();
        $rule = PointRule::create([
            'rule_key' => 'test_rule', 'rule_name' => 'Test rule', 'action_type' => 'earn',
            'source_type' => 'test_rule', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 0, 'is_active' => true,
            'cooldown_minutes' => 60,
        ]);

        $at = now()->subMonths(3);
        $txTime = $at->copy()->subMinutes(90);

        PointsTransaction::forceCreate([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'source_type' => 'test_rule',
            'status' => 'completed',
            'created_at' => $txTime,
            'updated_at' => $txTime,
        ]);

        $service = app(PointsService::class);
        $this->assertTrue($service->canEarnFromRule($user, $rule, $at));
    }

    public function test_engine_passes_occurred_at_correctly()
    {
        $user = User::factory()->create();
        PointRule::create([
            'rule_key' => 'test_rule_engine', 'rule_name' => 'Test rule engine', 'action_type' => 'earn',
            'source_type' => 'test_rule_engine', 'base_amount' => 10, 'multiplier' => 1,
            'xp_amount' => 10, 'is_active' => true,
            'max_daily_earnings' => 100,
        ]);

        // Max out today
        PointsTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'source_type' => 'test_rule_engine',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $past = now()->subMonths(3);

        $event = UserUsageEvent::forceCreate([
            'user_id' => $user->id,
            'event_type' => 'test_rule_engine',
            'occurred_at' => $past,
            'processed_at' => null,
            'context' => [],
        ]);

        $engine = app(GamificationRuleEngine::class);
        $engine->evaluate($event);

        $event->refresh();
        $this->assertNotNull($event->processed_at);

        $this->assertDatabaseHas('gamification_rule_logs', [
            'usage_event_id' => $event->id,
            'rule_key' => 'test_rule_engine',
            'result' => 'awarded',
        ]);
    }
}
