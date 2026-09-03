<?php

namespace Tests\Feature\Gamification;

use App\Jobs\RefreshLeaderboardCache;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StreakLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // GamificationService getLeaderboard uses Cache::remember for 10 minutes.
        // We need to flush the cache between tests so it doesn't return stale data.
        Cache::flush();
    }

    private function createPointStreak(User $user, int $currentStreak)
    {
        DB::table('point_streaks')->insert([
            'user_id' => $user->id,
            'current_streak' => $currentStreak,
            'longest_streak' => $currentStreak,
            'last_activity_date' => now()->toDateString(),
            'bonus_points_earned' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * เคสนี้คุมความเสี่ยงของการเพิ่ม leftJoin โดยเฉพาะ:
     * ถ้า point_streaks มีได้มากกว่า 1 แถวต่อ user แถวจะงอกจากการ join
     * แล้ว total() ของ paginator จะเกินจำนวน user จริง และ rank จะซ้ำข้ามหน้า
     * (ตอนนี้ point_streaks.user_id เป็น UNIQUE จึงต้องไม่งอก)
     */
    public function test_streak_leaderboard_pagination_totals_are_not_inflated_by_the_join()
    {
        foreach ([3, 10, 7] as $streak) {
            $this->createPointStreak(User::factory()->create(), $streak);
        }

        $service = app(GamificationService::class);

        $firstPage = $service->getLeaderboard('streak', 1, 2);

        $this->assertSame(3, $firstPage['pagination']['total']);
        $this->assertSame(2, $firstPage['pagination']['per_page']);
        $this->assertSame(1, $firstPage['pagination']['current_page']);
        $this->assertSame(2, $firstPage['pagination']['last_page']);
        $this->assertCount(2, $firstPage['leaderboard']);
        $this->assertSame([1, 2], array_column($firstPage['leaderboard'], 'rank'));
        $this->assertSame([10, 7], array_column($firstPage['leaderboard'], 'score'));

        $secondPage = $service->getLeaderboard('streak', 2, 2);

        $this->assertSame(3, $secondPage['pagination']['total']);
        $this->assertCount(1, $secondPage['leaderboard']);
        $this->assertSame([3], array_column($secondPage['leaderboard'], 'rank'));
        $this->assertSame([3], array_column($secondPage['leaderboard'], 'score'));
    }

    public function test_streak_leaderboard_orders_by_current_streak_desc()
    {
        $user1 = User::factory()->create();
        $this->createPointStreak($user1, 3);

        $user2 = User::factory()->create();
        $this->createPointStreak($user2, 10);

        $user3 = User::factory()->create();
        $this->createPointStreak($user3, 7);

        $service = app(GamificationService::class);
        $result = $service->getLeaderboard('streak');

        $leaderboard = $result['leaderboard'];

        $this->assertCount(3, $leaderboard);

        // User with streak 10 should be first
        $this->assertEquals(10, $leaderboard[0]['score']);
        $this->assertEquals(1, $leaderboard[0]['rank']);

        // User with streak 7 should be second
        $this->assertEquals(7, $leaderboard[1]['score']);
        $this->assertEquals(2, $leaderboard[1]['rank']);

        // User with streak 3 should be third
        $this->assertEquals(3, $leaderboard[2]['score']);
        $this->assertEquals(3, $leaderboard[2]['rank']);
    }

    public function test_user_without_streak_row_is_included_with_zero_score()
    {
        $userA = User::factory()->create();
        $this->createPointStreak($userA, 5);

        $userB = User::factory()->create();
        // User B has no row in point_streaks

        $service = app(GamificationService::class);
        $result = $service->getLeaderboard('streak');

        $leaderboard = collect($result['leaderboard']);

        $this->assertCount(2, $leaderboard);

        $userAResult = $leaderboard->firstWhere('user_id', $userA->id);
        $userBResult = $leaderboard->firstWhere('user_id', $userB->id);

        $this->assertNotNull($userAResult);
        $this->assertEquals(5, $userAResult['score']);

        $this->assertNotNull($userBResult);
        $this->assertEquals(0, $userBResult['score']);
    }

    /**
     * ค่า streak ต้องมาจาก alias ของ query เดียวกัน ไม่ใช่ lazy-load ความสัมพันธ์รายคน
     * ถ้าอ่านผ่าน $user->pointStreak ผลลัพธ์จะยังถูก แต่ยิง query เพิ่มคนละ 1 ครั้ง (N+1)
     * เคสนี้จึงคุมด้วยจำนวน query ไม่ใช่ค่าที่ได้
     */
    public function test_streak_leaderboard_does_not_run_a_query_per_user()
    {
        foreach (range(1, 5) as $streak) {
            $this->createPointStreak(User::factory()->create(), $streak);
        }

        $service = app(GamificationService::class);

        $countQueriesFor = function () use ($service) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $result = $service->getLeaderboard('streak', 1, 20);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            return [count($queries), count($result['leaderboard'])];
        };

        [$queriesForFive, $rowsForFive] = $countQueriesFor();
        $this->assertSame(5, $rowsForFive);

        // เพิ่มผู้ใช้อีกเท่าตัว แล้ววัดใหม่ — จำนวน query ต้องเท่าเดิม
        foreach (range(6, 10) as $streak) {
            $this->createPointStreak(User::factory()->create(), $streak);
        }

        [$queriesForTen, $rowsForTen] = $countQueriesFor();
        $this->assertSame(10, $rowsForTen);

        $this->assertSame(
            $queriesForFive,
            $queriesForTen,
            "จำนวน query โตตามจำนวนผู้ใช้ ({$queriesForFive} -> {$queriesForTen}) = ยังมี lazy-load รายคนอยู่"
        );
    }

    public function test_refresh_leaderboard_cache_job_runs_without_failing()
    {
        $user1 = User::factory()->create();
        $this->createPointStreak($user1, 2);

        $user2 = User::factory()->create();
        $this->createPointStreak($user2, 8);

        $job = new RefreshLeaderboardCache;
        $service = app(GamificationService::class);

        $job->handle($service);

        $this->expectNotToPerformAssertions();
    }
}
