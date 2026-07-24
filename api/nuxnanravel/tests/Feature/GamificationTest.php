<?php

namespace Tests\Feature;

use App\Enums\UsageEventType;
use App\Models\GamificationRuleLog;
use App\Models\PointRule;
use App\Models\User;
use App\Models\UserUsageEvent;
use App\Services\GamificationService;
use App\Services\ActivitySummaryService;
use App\Services\GamificationRuleEngine;
use App\Services\UsageEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed only necessary data or use factory
    }

    public function test_login_awards_xp_and_points_daily()
    {
        $user = User::factory()->create(['pp' => 0]);

        UsageEventService::fire($user, UsageEventType::LOGIN->value);

        $this->assertDatabaseHas('user_usage_events', [
            'user_id' => $user->id,
            'event_type' => 'login',
        ]);
    }

    public function test_quiz_pass_awards_xp_once_per_source()
    {
        $user = User::factory()->create();
        $quizId = 101;

        UsageEventService::fire($user, UsageEventType::QUIZ_PASS->value, 'quiz', $quizId);
        UsageEventService::fire($user, UsageEventType::QUIZ_PASS->value, 'quiz', $quizId); // Duplicate

        $this->assertEquals(1, UserUsageEvent::where('user_id', $user->id)
            ->where('event_type', 'quiz_pass')
            ->where('source_id', $quizId)
            ->count());
    }

    public function test_gamification_quiz_pass_awards_xp_without_platform_pp(): void
    {
        PointRule::create([
            'rule_key' => 'quiz_pass', 'rule_name' => 'Quiz pass', 'action_type' => 'earn',
            'source_type' => 'quiz_pass', 'base_amount' => 0, 'multiplier' => 1,
            'xp_amount' => 500, 'is_active' => true,
        ]);
        $user = User::factory()->create(['pp' => 0, 'xp' => 0]);

        UsageEventService::fire($user, UsageEventType::QUIZ_PASS->value, 'quiz', 301);

        $user->refresh();
        $this->assertSame(0.0, (float) $user->pp);
        $this->assertSame(500, (int) $user->xp);
    }

    public function test_points_earn_endpoint_is_not_available_to_normal_users(): void
    {
        $user = User::factory()->create(['pp' => 0]);

        $this->actingAs($user, 'api')->postJson('/api/points/earn', [
            'source_type' => 'game_snake', 'amount' => 999,
        ])->assertNotFound();

        $this->assertSame(0.0, (float) $user->fresh()->pp);
    }

    public function test_streak_bonus_awards_xp_without_platform_pp(): void
    {
        $user = User::factory()->create(['pp' => 0, 'xp' => 0]);

        app(GamificationService::class)->recordActivity($user);

        $user->refresh();
        $this->assertSame(0.0, (float) $user->pp);
        $this->assertSame(10, (int) $user->xp);
    }

    public function test_lesson_complete_awards_xp_once_per_source()
    {
        $user = User::factory()->create();
        $lessonId = 201;

        UsageEventService::fire($user, UsageEventType::LESSON_COMPLETE->value, 'lesson', $lessonId);
        UsageEventService::fire($user, UsageEventType::LESSON_COMPLETE->value, 'lesson', $lessonId); // Duplicate

        $this->assertEquals(1, UserUsageEvent::where('user_id', $user->id)
            ->where('event_type', 'lesson_complete')
            ->where('source_id', $lessonId)
            ->count());
    }

    public function test_idempotency_key_generation_by_strategy()
    {
        $service = new UsageEventService;
        $userId = 1;

        // Daily (Strategy for LOGIN)
        $key1 = $this->invokeMethod($service, 'generateIdempotencyKey', [$userId, 'login', null]);
        $key2 = $this->invokeMethod($service, 'generateIdempotencyKey', [$userId, 'login', null]);
        $this->assertEquals($key1, $key2);

        // Always (Strategy for POST_CREATE)
        $key3 = $this->invokeMethod($service, 'generateIdempotencyKey', [$userId, 'post_create', null]);
        $key4 = $this->invokeMethod($service, 'generateIdempotencyKey', [$userId, 'post_create', null]);
        $this->assertNotEquals($key3, $key4);
    }

    public function test_activity_summary_aggregation()
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        // Create some events and logs manually
        UserUsageEvent::create([
            'user_id' => $user->id,
            'event_type' => 'login',
            'idempotency_key' => 'test1',
            'occurred_at' => now(),
        ]);

        GamificationRuleLog::create([
            'user_id' => $user->id,
            'usage_event_id' => 1,
            'rule_key' => 'login',
            'result' => 'awarded',
            'points_awarded' => 10,
            'xp_awarded' => 100,
            'evaluated_at' => now(),
        ]);

        $summaryService = new ActivitySummaryService;
        $summaryService->updateDailySummary($user, $date);

        $this->assertDatabaseHas('user_activity_summaries', [
            'user_id' => $user->id,
            'summary_date' => $date.' 00:00:00',
            'points_earned' => 10,
            'xp_earned' => 100,
        ]);
    }

    public function test_xp_amount_configurable_per_rule()
    {
        $rule = PointRule::create([
            'rule_key' => 'custom_xp',
            'rule_name' => 'Custom XP Rule',
            'action_type' => 'earn',
            'source_type' => 'custom_xp',
            'base_amount' => 5,
            'multiplier' => 1,
            'xp_amount' => 123,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $event = UserUsageEvent::create([
            'user_id' => $user->id,
            'event_type' => 'custom_xp',
            'idempotency_key' => 'test_xp',
            'occurred_at' => now(),
        ]);

        $engine = new GamificationRuleEngine;
        $engine->evaluate($event);

        $this->assertDatabaseHas('gamification_rule_logs', [
            'user_id' => $user->id,
            'rule_key' => 'custom_xp',
            'xp_awarded' => 123,
        ]);
    }

    /**
     * Helper to call protected methods.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
