<?php

namespace Tests\Feature\Performance;

use App\Http\Controllers\Api\Play\ActivityController;
use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Feed comment loading must stay bounded: ActivityController::loadActivityableForFeed()
 * preloads the latest 3 comments per post (native Laravel 11+ per-parent eager limit),
 * so Post::getComments() reads them in-memory and issues 0 extra queries per post.
 *
 * Guards item 5: before this, getComments() fell to its query branch (~5 queries/post).
 * Mutation check: removing the postComments preload from loadActivityableForFeed makes
 * the "getComments adds 0 queries" assertion fail (queries grow per post again).
 */
class FeedCommentsQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function seedPosts(int $count, int $author, int $commentsEach = 5): void
    {
        for ($i = 0; $i < $count; $i++) {
            $postId = DB::table('posts')->insertGetId([
                'user_id' => $author,
                'content' => "post {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('activities')->insert([
                'user_id' => $author,
                'activityable_type' => Post::class,
                'activityable_id' => $postId,
                'activity_type' => 'post',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $rows = [];
            for ($c = 0; $c < $commentsEach; $c++) {
                $rows[] = [
                    'post_id' => $postId,
                    'user_id' => $author,
                    'content' => "comment {$c}",
                    'created_at' => now()->addSeconds($c),
                    'updated_at' => now(),
                ];
            }
            DB::table('post_comments')->insert($rows);
        }
    }

    /**
     * @return array{0:int,1:int,2:int} [loadQueries, getCommentsExtraQueries, maxCommentsPerPost]
     */
    private function measure(int $perPage): array
    {
        $ctrl = app(ActivityController::class);
        $method = new ReflectionMethod($ctrl, 'loadActivityableForFeed');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $activities = Activity::whereIn('activityable_type', [Post::class])
            ->with(['activityable', 'user' => fn ($q) => $q->withCardCounts()])
            ->latest()
            ->paginate($perPage);
        $method->invoke($ctrl, $activities);
        $loadQueries = count(DB::getQueryLog());

        // Serializing the feed calls getComments() per post — must add no queries.
        DB::flushQueryLog();
        $maxComments = 0;
        foreach ($activities as $activity) {
            $comments = $activity->activityable->getComments();
            $maxComments = max($maxComments, count($comments));
        }
        $extra = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$loadQueries, $extra, $maxComments];
    }

    public function test_feed_comment_loading_is_bounded_and_capped_at_three(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $this->actingAs($viewer, 'api');

        // small feed
        $this->seedPosts(3, $author->id);
        [$loadSmall, $extraSmall, $maxSmall] = $this->measure(15);

        // grow to 12 posts
        $this->seedPosts(9, $author->id);
        [$loadBig, $extraBig, $maxBig] = $this->measure(15);

        // getComments() reads preloaded relation → zero extra queries at any size (the core guard)
        $this->assertSame(0, $extraSmall, "getComments เพิ่มคิวรีที่ 3 โพสต์ ({$extraSmall}) — preload หลุด");
        $this->assertSame(0, $extraBig, "getComments เพิ่มคิวรีที่ 12 โพสต์ ({$extraBig}) — N+1 กลับมา");

        // loadActivityableForFeed query count must not grow with post count
        $this->assertLessThanOrEqual(
            $loadSmall + 2,
            $loadBig,
            "query โตตามจำนวนโพสต์ (3 โพสต์={$loadSmall}, 12 โพสต์={$loadBig}) — eager-limit ไม่ทำงาน"
        );

        // per-parent limit: never more than 3 comments/post, and comments actually load
        $this->assertLessThanOrEqual(3, $maxBig, "โหลดคอมเมนต์เกิน 3/โพสต์ ({$maxBig}) — per-parent limit เสีย");
        $this->assertGreaterThan(0, $maxBig, 'ไม่มีคอมเมนต์ถูกโหลดเลย — ตรวจ preload');
    }
}
