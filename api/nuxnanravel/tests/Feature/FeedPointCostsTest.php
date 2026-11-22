<?php

namespace Tests\Feature;

use App\Jobs\ProcessUsageEvent;
use App\Models\PointsTransaction;
use App\Models\Poll;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Services\FeedPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class FeedPointCostsTest extends TestCase
{
    use RefreshDatabase;

    private User $platform;

    private User $author;

    private User $payer;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([ProcessUsageEvent::class]);
        $this->platform = User::factory()->create(['id' => 1, 'pp' => 1000]);
        $this->author = User::factory()->create(['pp' => 1000]);
        $this->payer = User::factory()->create(['pp' => 1000]);
        $this->post = Post::create([
            'user_id' => $this->author->id,
            'content' => 'Feed costs test',
            'privacy_settings' => 3,
            'status' => 1,
        ]);
    }

    private function assertLedger(User $user, string $source, int $amount): void
    {
        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'source_type' => $source,
            'amount' => $amount,
            'status' => 'completed',
        ]);
    }

    public function test_post_creation_costs_480_and_records_the_charge(): void
    {
        $this->actingAs($this->payer, 'api')->postJson('/api/posts', [
            'content' => 'A new public post', 'privacy_settings' => 3,
        ])->assertCreated()->assertJsonPath('user_pp', 520);

        $this->assertSame(520, $this->payer->fresh()->pp);
        $this->assertLedger($this->payer, 'feed_post_create', 480);
        $this->assertDatabaseHas('posts', ['user_id' => $this->payer->id, 'content' => 'A new public post']);
    }

    public function test_insufficient_post_creation_creates_no_post_or_ledger_charge(): void
    {
        $this->payer->update(['pp' => 479]);
        $response = $this->actingAs($this->payer, 'api')->postJson('/api/posts', [
            'content' => 'Cannot afford this', 'privacy_settings' => 3,
        ]);
        $this->assertContains($response->status(), [403, 422]);
        $this->assertDatabaseMissing('posts', ['content' => 'Cannot afford this']);
        $this->assertDatabaseMissing('points_transactions', ['source_type' => 'feed_post_create']);
        $this->assertSame(479, $this->payer->fresh()->pp);
    }

    public function test_like_and_unlike_distribute_points_and_record_each_charge(): void
    {
        $uri = "/api/posts/{$this->post->id}/like";
        $this->actingAs($this->payer, 'api')->postJson($uri)->assertOk()->assertJsonPath('user_pp', 880);
        $this->assertSame(1060, $this->author->fresh()->pp);
        $this->assertSame(1060, $this->platform->fresh()->pp);
        $this->assertTrue($this->post->likedPost()->where('user_id', $this->payer->id)->exists());
        $this->assertLedger($this->payer, 'feed_like', 120);
        $this->assertLedger($this->author, 'feed_like_reward', 60);
        $this->assertLedger($this->platform, 'feed_platform_fee', 60);

        $this->postJson($uri)->assertOk()->assertJsonPath('user_pp', 820);
        $this->assertSame(820, $this->payer->fresh()->pp);
        $this->assertSame(1060, $this->author->fresh()->pp);
        $this->assertSame(1120, $this->platform->fresh()->pp);
        $this->assertFalse($this->post->likedPost()->where('user_id', $this->payer->id)->exists());
        $this->assertLedger($this->payer, 'feed_unlike', 60);
    }

    public function test_dislike_fines_an_affordable_author_and_undislike_costs_60(): void
    {
        $uri = "/api/posts/{$this->post->id}/dislike";
        $this->actingAs($this->payer, 'api')->postJson($uri)->assertOk()->assertJsonPath('user_pp', 880);
        $this->assertSame(940, $this->author->fresh()->pp);
        $this->assertSame(1180, $this->platform->fresh()->pp);
        $this->assertLedger($this->payer, 'feed_dislike', 120);
        $this->assertLedger($this->author, 'feed_dislike_fine', 60);

        $this->postJson($uri)->assertOk()->assertJsonPath('user_pp', 820);
        $this->assertSame(940, $this->author->fresh()->pp);
        $this->assertSame(1240, $this->platform->fresh()->pp);
        $this->assertLedger($this->payer, 'feed_undislike', 60);
    }

    public function test_dislike_skips_author_fine_when_author_cannot_afford_it(): void
    {
        $this->author->update(['pp' => 59]);
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/dislike")
            ->assertOk()->assertJsonPath('user_pp', 880);
        $this->assertSame(59, $this->author->fresh()->pp);
        $this->assertSame(1120, $this->platform->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['source_type' => 'feed_dislike_fine']);
    }

    public function test_comment_costs_240_and_rewards_post_author(): void
    {
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Comment'])
            ->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(760, $this->payer->fresh()->pp);
        $this->assertSame(1120, $this->author->fresh()->pp);
        $this->assertSame(1120, $this->platform->fresh()->pp);
        $this->assertSame(1, $this->post->fresh()->comments);
        $this->assertLedger($this->payer, 'feed_comment', 240);
        $this->assertLedger($this->author, 'feed_comment_reward', 120);
    }

    public function test_self_comment_sends_both_shares_to_platform(): void
    {
        $this->actingAs($this->author, 'api')->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Own comment'])
            ->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(760, $this->author->fresh()->pp);
        $this->assertSame(1240, $this->platform->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $this->author->id, 'transaction_type' => 'earn']);
    }

    public function test_reply_costs_300_and_rewards_the_parent_author(): void
    {
        $comment = $this->post->postComments()->create(['user_id' => $this->author->id, 'content' => 'Parent']);
        $this->actingAs($this->payer, 'api')->postJson("/api/post_comments/{$comment->id}/replies", ['content' => 'Reply'])
            ->assertOk()->assertJsonPath('user_pp', 700);
        $this->assertSame(700, $this->payer->fresh()->pp);
        $this->assertSame(1150, $this->author->fresh()->pp);
        $this->assertSame(1150, $this->platform->fresh()->pp);
        $this->assertSame(1, $comment->fresh()->replies);
        $this->assertDatabaseHas('post_comments', ['parent_post_comment_id' => $comment->id, 'user_id' => $this->payer->id]);
        $this->assertLedger($this->payer, 'feed_reply', 300);
        $this->assertLedger($this->author, 'feed_reply_reward', 150);
    }

    public function test_insufficient_like_returns_422_without_attaching_a_reaction(): void
    {
        $this->payer->update(['pp' => 119]);
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/like")
            ->assertStatus(422)->assertJsonPath('code', 'INSUFFICIENT_PP')
            ->assertJsonPath('required_pp', 120)->assertJsonPath('current_pp', 119);
        $this->assertFalse($this->post->likedPost()->where('user_id', $this->payer->id)->exists());
        $this->assertSame(119, $this->payer->fresh()->pp);
        $this->assertDatabaseCount('points_transactions', 0);
    }

    public function test_insufficient_comment_and_reply_do_not_create_rows_or_increment_counters(): void
    {
        $comment = $this->post->postComments()->create(['user_id' => $this->author->id, 'content' => 'Parent']);
        $this->payer->update(['pp' => 239]);
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Too costly'])
            ->assertStatus(422)->assertJsonPath('code', 'INSUFFICIENT_PP')->assertJsonPath('required_pp', 240);
        $this->postJson("/api/post_comments/{$comment->id}/replies", ['content' => 'Too costly reply'])
            ->assertStatus(422)->assertJsonPath('code', 'INSUFFICIENT_PP')->assertJsonPath('required_pp', 300);
        $this->assertDatabaseCount('post_comments', 1);
        $this->assertSame(0, $this->post->fresh()->comments);
        $this->assertSame(0, $comment->fresh()->replies);
    }

    public function test_switching_reactions_does_not_refund_the_previous_reaction(): void
    {
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/like")->assertOk();
        $this->postJson("/api/posts/{$this->post->id}/dislike")->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(1000, $this->author->fresh()->pp);
        $this->assertSame(1240, $this->platform->fresh()->pp);
        $this->assertFalse($this->post->likedPost()->where('user_id', $this->payer->id)->exists());
        $this->assertTrue($this->post->dislikedPost()->where('user_id', $this->payer->id)->exists());
        $this->assertSame(0, $this->post->fresh()->likes);
        $this->assertSame(1, $this->post->fresh()->dislikes);
    }

    public function test_image_like_and_dislike_use_the_same_costs_and_distribution(): void
    {
        $image = $this->post->postImages()->create(['filename' => 'test.jpg']);
        $this->actingAs($this->payer, 'api')->postJson("/api/post_images/{$image->id}/like")
            ->assertOk()->assertJsonPath('user_pp', 880);
        $this->assertSame(1060, $this->author->fresh()->pp);
        $this->assertSame(1060, $this->platform->fresh()->pp);
        $this->postJson("/api/post_images/{$image->id}/dislike")->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(1000, $this->author->fresh()->pp);
        $this->assertSame(1240, $this->platform->fresh()->pp);
    }

    public function test_image_dislike_accepts_exactly_the_required_balance(): void
    {
        $image = $this->post->postImages()->create(['filename' => 'test.jpg']);
        $this->payer->update(['pp' => 120]);
        $this->actingAs($this->payer, 'api')->postJson("/api/post_images/{$image->id}/dislike")
            ->assertOk()->assertJsonPath('user_pp', 0);
        $this->assertSame(0, $this->payer->fresh()->pp);
    }

    public function test_poll_comment_costs_240_and_rewards_owner(): void
    {
        $poll = Poll::create(['user_id' => $this->author->id, 'title' => 'Poll', 'start_date' => now(), 'end_date' => now()->addDay()]);
        $this->actingAs($this->payer, 'api')->postJson("/api/polls/{$poll->id}/comment", ['content' => 'Poll comment'])
            ->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(1120, $this->author->fresh()->pp);
        $this->assertSame(1120, $this->platform->fresh()->pp);
        $this->assertLedger($this->payer, 'feed_comment', 240);
    }

    public function test_restricted_payer_cannot_attach_reaction(): void
    {
        $this->payer->update(['points_suspended' => true]);
        $this->actingAs($this->payer, 'api')->postJson("/api/posts/{$this->post->id}/like")
            ->assertForbidden()->assertJsonPath('code', 'ACCOUNT_RESTRICTED');
        $this->assertSame(1000, $this->payer->fresh()->pp);
        $this->assertFalse($this->post->likedPost()->where('user_id', $this->payer->id)->exists());
    }

    public function test_domain_failure_rolls_back_the_charge_and_domain_write(): void
    {
        try {
            app(FeedPointsService::class)->charge($this->payer, 240, 'feed_comment', $this->post->id, 'Rollback test', [], function () {
                PostComment::create(['post_id' => $this->post->id, 'user_id' => $this->payer->id, 'content' => 'Rolled back']);
                throw new \RuntimeException('Domain write failed');
            });
            $this->fail('Expected domain failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Domain write failed', $exception->getMessage());
        }
        $this->assertSame(1000, $this->payer->fresh()->pp);
        $this->assertDatabaseMissing('post_comments', ['content' => 'Rolled back']);
        $this->assertDatabaseCount('points_transactions', 0);
    }

    public function test_post_with_poll_pool_deducts_the_pool_exactly_once(): void
    {
        $this->payer->update(['pp' => 580]);
        $this->actingAs($this->payer, 'api')->postJson('/api/posts', [
            'content' => 'A rewarded poll', 'privacy_settings' => 3,
            'is_poll' => true, 'poll_title' => 'Question?',
            'poll_options' => ['First', 'Second'], 'poll_points_pool' => 100,
        ])->assertCreated()->assertJsonPath('user_pp', 0);
        $this->assertSame(0, $this->payer->fresh()->pp);
        $this->assertDatabaseHas('polls', ['user_id' => $this->payer->id, 'points_pool' => 100]);
        $this->assertLedger($this->payer, 'feed_post_create', 480);
        $this->assertSame(1, PointsTransaction::where('source_type', 'feed_post_create')->count());
    }

    public function test_post_poll_requires_the_combined_post_and_pool_balance(): void
    {
        $this->payer->update(['pp' => 579]);
        $response = $this->actingAs($this->payer, 'api')->postJson('/api/posts', [
            'content' => 'Unaffordable poll', 'privacy_settings' => 3,
            'is_poll' => true, 'poll_title' => 'Question?',
            'poll_options' => ['First', 'Second'], 'poll_points_pool' => 100,
        ]);
        $this->assertContains($response->status(), [403, 422]);
        $response->assertJsonPath('required_pp', 580);
        $this->assertSame(579, $this->payer->fresh()->pp);
        $this->assertDatabaseCount('polls', 0);
        $this->assertDatabaseMissing('posts', ['content' => 'Unaffordable poll']);
        $this->assertDatabaseCount('points_transactions', 0);
    }

    public function test_self_reply_sends_both_shares_to_platform(): void
    {
        $comment = $this->post->postComments()->create(['user_id' => $this->payer->id, 'content' => 'My parent']);
        $this->actingAs($this->payer, 'api')->postJson("/api/post_comments/{$comment->id}/replies", ['content' => 'My reply'])
            ->assertOk()->assertJsonPath('user_pp', 700);
        $this->assertSame(700, $this->payer->fresh()->pp);
        $this->assertSame(1300, $this->platform->fresh()->pp);
        $this->assertSame(1000, $this->author->fresh()->pp);
    }

    public function test_self_poll_comment_sends_both_shares_to_platform(): void
    {
        $poll = Poll::create(['user_id' => $this->payer->id, 'title' => 'Own poll', 'start_date' => now(), 'end_date' => now()->addDay()]);
        $this->actingAs($this->payer, 'api')->postJson("/api/polls/{$poll->id}/comment", ['content' => 'Own poll comment'])
            ->assertOk()->assertJsonPath('user_pp', 760);
        $this->assertSame(760, $this->payer->fresh()->pp);
        $this->assertSame(1240, $this->platform->fresh()->pp);
    }

    public function test_platform_payer_receives_its_share_without_overwriting_the_charge(): void
    {
        $this->actingAs($this->platform, 'api')->postJson("/api/posts/{$this->post->id}/like")
            ->assertOk()->assertJsonPath('user_pp', 940);
        $this->assertSame(940, $this->platform->fresh()->pp);
        $this->assertSame(1060, $this->author->fresh()->pp);
        $this->assertLedger($this->platform, 'feed_like', 120);
        $this->assertLedger($this->platform, 'feed_platform_fee', 60);
    }

    public function test_platform_self_comment_preserves_both_credits_to_the_same_payer(): void
    {
        $this->post->update(['user_id' => $this->platform->id]);
        $this->actingAs($this->platform, 'api')->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Platform comment'])
            ->assertOk()->assertJsonPath('user_pp', 1000);
        $this->assertSame(1000, $this->platform->fresh()->pp);
        $this->assertLedger($this->platform, 'feed_comment', 240);
    }
}
