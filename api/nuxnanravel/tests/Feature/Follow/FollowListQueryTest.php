<?php

namespace Tests\Feature\Follow;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * กัน N+1 ถอยใน FollowController::followers/following (is_following รายแถว)
 * หลัก "query ต้องไม่โตตามจำนวนแถว" + ยืนยันค่า is_following ยังถูกหลัง refactor
 */
class FollowListQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_followers_endpoint_does_not_scale_queries_with_follower_count(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        $measure = function () use ($viewer, $target): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($viewer, 'api')
                ->getJson("/api/follow/users/{$target->id}/followers")
                ->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        User::factory()->count(3)->create()->each(
            fn ($u) => Follow::create(['follower_id' => $u->id, 'followed_id' => $target->id])
        );
        $small = $measure();

        User::factory()->count(10)->create()->each(
            fn ($u) => Follow::create(['follower_id' => $u->id, 'followed_id' => $target->id])
        );
        $big = $measure();

        $this->assertLessThanOrEqual(
            $small + 2,
            $big,
            "followers query โตตามจำนวนแถว (น้อย=$small, มาก=$big) — is_following อาจกลับไป query รายแถว"
        );
    }

    public function test_following_endpoint_does_not_scale_queries_with_row_count(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        $measure = function () use ($viewer, $target): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($viewer, 'api')
                ->getJson("/api/follow/users/{$target->id}/following")
                ->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        User::factory()->count(3)->create()->each(
            fn ($u) => Follow::create(['follower_id' => $target->id, 'followed_id' => $u->id])
        );
        $small = $measure();

        User::factory()->count(10)->create()->each(
            fn ($u) => Follow::create(['follower_id' => $target->id, 'followed_id' => $u->id])
        );
        $big = $measure();

        $this->assertLessThanOrEqual(
            $small + 2,
            $big,
            "following query โตตามจำนวนแถว (น้อย=$small, มาก=$big)"
        );
    }

    public function test_followers_is_following_flag_reflects_auth_viewer(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();

        // a และ b ต่าง follow target (จึงโผล่ในลิสต์ followers ของ target)
        Follow::create(['follower_id' => $a->id, 'followed_id' => $target->id]);
        Follow::create(['follower_id' => $b->id, 'followed_id' => $target->id]);
        // viewer follow เฉพาะ a
        Follow::create(['follower_id' => $viewer->id, 'followed_id' => $a->id]);

        $rows = collect(
            $this->actingAs($viewer, 'api')
                ->getJson("/api/follow/users/{$target->id}/followers")
                ->assertOk()
                ->json('data.followers')
        );

        $this->assertTrue((bool) $rows->firstWhere('id', $a->id)['is_following'], 'viewer follow a → ควร true');
        $this->assertFalse((bool) $rows->firstWhere('id', $b->id)['is_following'], 'viewer ไม่ follow b → ควร false');
    }
}
