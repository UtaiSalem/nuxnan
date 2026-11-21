<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPrivacyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $friend;

    protected $stranger;

    protected $onlyMe;

    protected $friendsOnly;

    protected $public;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->friend = User::factory()->create();
        $this->stranger = User::factory()->create();

        // Owner <-> Friend (accepted friendship in friendships table)
        $this->owner->befriend($this->friend);
        $this->friend->acceptFriendRequest($this->owner);

        $this->onlyMe = Post::create(['user_id' => $this->owner->id, 'content' => 'only me', 'privacy_settings' => 1, 'status' => 1]);
        $this->friendsOnly = Post::create(['user_id' => $this->owner->id, 'content' => 'friends', 'privacy_settings' => 2, 'status' => 1]);
        $this->public = Post::create(['user_id' => $this->owner->id, 'content' => 'public', 'privacy_settings' => 3, 'status' => 1]);
    }

    private function idsFor(User $viewer, string $uri): array
    {
        $response = $this->actingAs($viewer, 'api')->getJson($uri);
        $response->assertOk();

        return collect($response->json('posts'))->pluck('id')->sort()->values()->all();
    }

    private function ownerPostsUri(): string
    {
        return "/api/posts?user_id={$this->owner->id}";
    }

    public function test_index_shows_only_public_posts_to_strangers(): void
    {
        $this->assertSame(
            [$this->public->id],
            $this->idsFor($this->stranger, $this->ownerPostsUri())
        );
    }

    public function test_index_shows_friends_posts_to_friends(): void
    {
        $expected = collect([$this->friendsOnly->id, $this->public->id])->sort()->values()->all();

        $this->assertSame(
            $expected,
            $this->idsFor($this->friend, $this->ownerPostsUri())
        );
    }

    public function test_index_shows_all_own_posts_to_owner(): void
    {
        $expected = collect([$this->onlyMe->id, $this->friendsOnly->id, $this->public->id])->sort()->values()->all();

        $this->assertSame(
            $expected,
            $this->idsFor($this->owner, $this->ownerPostsUri())
        );
    }

    public function test_index_without_user_filter_hides_private_posts_of_others(): void
    {
        $ids = $this->idsFor($this->stranger, '/api/posts');

        $this->assertContains($this->public->id, $ids);
        $this->assertNotContains($this->onlyMe->id, $ids);
        $this->assertNotContains($this->friendsOnly->id, $ids);
    }

    public function test_show_friends_post_is_blocked_for_strangers_and_open_for_friends(): void
    {
        $this->actingAs($this->stranger, 'api')
            ->getJson("/api/posts/{$this->friendsOnly->id}")
            ->assertStatus(403);

        $this->actingAs($this->friend, 'api')
            ->getJson("/api/posts/{$this->friendsOnly->id}")
            ->assertStatus(200);
    }

    public function test_show_only_me_post_is_blocked_for_friends_and_open_for_owner(): void
    {
        $this->actingAs($this->friend, 'api')
            ->getJson("/api/posts/{$this->onlyMe->id}")
            ->assertStatus(403);

        $this->actingAs($this->owner, 'api')
            ->getJson("/api/posts/{$this->onlyMe->id}")
            ->assertStatus(200);
    }

    public function test_show_public_post_is_open_to_strangers(): void
    {
        $this->actingAs($this->stranger, 'api')
            ->getJson("/api/posts/{$this->public->id}")
            ->assertStatus(200);
    }
}
