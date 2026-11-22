<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileFriendshipAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $friend;

    protected $stranger;

    protected $requester;

    protected $invitee;

    protected $ownerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner Display', 'username' => 'owner_handle_p1']);
        $this->friend = User::factory()->create();
        $this->stranger = User::factory()->create();
        $this->requester = User::factory()->create(); // ส่งคำขอมาหา owner (owner ยังไม่ตอบ)
        $this->invitee = User::factory()->create();   // owner ส่งคำขอไปหา (ยังไม่ตอบ)

        $this->ownerProfile = UserProfile::create([
            'user_id' => $this->owner->id,
            'first_name' => 'Owner',
            'privacy_settings' => 'public',
        ]);

        foreach ([$this->friend, $this->stranger, $this->requester, $this->invitee] as $user) {
            UserProfile::create(['user_id' => $user->id]);
        }

        $this->owner->befriend($this->friend);
        $this->friend->acceptFriendRequest($this->owner);
        $this->requester->befriend($this->owner);
        $this->owner->befriend($this->invitee);
    }

    private function show(User $viewer, ?string $identifier = null)
    {
        return $this->actingAs($viewer, 'api')
            ->getJson('/api/users/'.($identifier ?? $this->owner->reference_code).'/show');
    }

    private function makeUserWithProfile(): User
    {
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_friendship_status_comes_from_the_friendships_table(): void
    {
        $expected = [
            'self' => $this->owner,
            'friends' => $this->friend,
            'none' => $this->stranger,
            'pending_sent' => $this->requester,
            'pending_received' => $this->invitee,
        ];

        foreach ($expected as $status => $viewer) {
            $res = $this->show($viewer);
            $res->assertStatus(200);
            $this->assertSame($status, $res->json('friendship_status.status'), "viewer expected [{$status}]");
        }
    }

    public function test_denied_request_counts_as_none(): void
    {
        $other = $this->makeUserWithProfile();
        $other->befriend($this->owner);
        $this->owner->denyFriendRequest($other);

        $res = $this->show($other);

        $res->assertStatus(200);
        $this->assertSame('none', $res->json('friendship_status.status'));
    }

    public function test_friends_count_and_mutual_friends_are_real(): void
    {
        $friend2 = $this->makeUserWithProfile();

        $this->owner->befriend($friend2);
        $friend2->acceptFriendRequest($this->owner);
        $this->stranger->befriend($friend2);
        $friend2->acceptFriendRequest($this->stranger);

        $res = $this->show($this->stranger);

        $res->assertStatus(200);
        $this->assertSame(2, $res->json('data.friends_count'));
        $this->assertSame(2, $res->json('data.friends'));
        $this->assertSame(1, $res->json('data.mutual_friends'));
    }

    public function test_friend_sees_friends_only_posts_in_the_timeline(): void
    {
        foreach ([1, 2, 3] as $privacy) {
            $post = Post::create([
                'user_id' => $this->owner->id,
                'content' => "post privacy {$privacy}",
                'privacy_settings' => $privacy,
                'status' => 1,
            ]);

            Activity::create([
                'user_id' => $this->owner->id,
                'activity_type' => 'createpost',
                'activityable_type' => Post::class,
                'activityable_id' => $post->id,
            ]);
        }

        $url = "/api/users/{$this->owner->reference_code}/activities";

        foreach ([3 => $this->owner, 2 => $this->friend, 1 => $this->stranger] as $count => $viewer) {
            $res = $this->actingAs($viewer, 'api')->getJson($url);
            $res->assertStatus(200);
            $this->assertCount($count, $res->json('activities'));
        }
    }

    public function test_friends_only_profile_is_not_opened_by_a_pending_request(): void
    {
        $this->ownerProfile->update(['privacy_settings' => 'friends']);

        $this->assertFalse($this->show($this->requester)->json('can_view_full_profile'));
        $this->assertFalse($this->show($this->stranger)->json('can_view_full_profile'));
        $this->assertTrue($this->show($this->friend)->json('can_view_full_profile'));
    }

    public function test_profile_resolves_by_id_reference_code_personal_code_and_username_but_not_display_name(): void
    {
        $byId = $this->show($this->stranger, (string) $this->owner->id);
        $byId->assertStatus(200);
        $this->assertSame($this->owner->id, $byId->json('data.user_id'));

        $this->show($this->stranger, $this->owner->reference_code)->assertStatus(200);
        $this->show($this->stranger, (string) $this->owner->personal_code)->assertStatus(200);
        $this->show($this->stranger, 'owner_handle_p1')->assertStatus(200);

        $this->actingAs($this->stranger, 'api')
            ->getJson('/api/users/'.rawurlencode('Owner Display').'/show')
            ->assertStatus(404);
    }

    public function test_visits_count_once_per_viewer_per_day_and_only_the_owner_sees_them(): void
    {
        $visits = fn () => (int) DB::table('user_profiles')->where('user_id', $this->owner->id)->value('visits');

        $strangerRes = null;
        for ($i = 0; $i < 3; $i++) {
            $strangerRes = $this->show($this->stranger);
            $strangerRes->assertStatus(200);
        }
        $this->assertSame(1, $visits());

        $this->show($this->friend)->assertStatus(200);
        $this->assertSame(2, $visits());

        $this->show($this->owner)->assertStatus(200);
        $this->assertSame(2, $visits());

        $data = $strangerRes->json('data');
        $this->assertArrayNotHasKey('visits', $data);
        $this->assertArrayNotHasKey('visits_count', $data);

        $me = $this->actingAs($this->owner, 'api')->getJson('/api/profile/me');
        $me->assertStatus(200);
        $this->assertSame(2, $me->json('data.visits'));
    }

    public function test_profile_with_saved_social_links_renders(): void
    {
        $this->ownerProfile->update(['social_media_links' => ['facebook' => 'https://facebook.com/owner']]);

        $res = $this->show($this->stranger);
        $res->assertStatus(200);
        $this->assertSame('https://facebook.com/owner', $res->json('data.social_media_links.facebook'));

        $this->actingAs($this->owner, 'api')->getJson('/api/profile/me')->assertStatus(200);
    }

    public function test_profile_update_stores_social_links_once(): void
    {
        $this->actingAs($this->owner, 'api')
            ->putJson('/api/profile/update', ['social_media_links' => ['facebook' => 'https://facebook.com/owner']])
            ->assertStatus(200);

        $raw = DB::table('user_profiles')->where('user_id', $this->owner->id)->value('social_media_links');
        $this->assertStringStartsWith('{', (string) $raw);

        $res = $this->show($this->stranger);
        $this->assertSame('https://facebook.com/owner', $res->json('data.social_media_links.facebook'));
    }

    public function test_can_send_friend_request_flag(): void
    {
        $this->assertTrue($this->show($this->stranger)->json('data.can_send_friend_request'));
        $this->assertFalse($this->show($this->friend)->json('data.can_send_friend_request'));
        $this->assertFalse($this->show($this->requester)->json('data.can_send_friend_request'));
        $this->assertFalse($this->show($this->invitee)->json('data.can_send_friend_request'));

        $this->ownerProfile->update(['allow_friend_requests' => false]);
        $this->assertFalse($this->show($this->stranger)->json('data.can_send_friend_request'));
    }

    public function test_privacy_settings_read_and_write_as_plain_strings(): void
    {
        DB::table('user_profiles')->where('user_id', $this->owner->id)->update(['privacy_settings' => '"friends"']);

        $this->assertSame('friends', UserProfile::where('user_id', $this->owner->id)->first()->privacy_settings);

        UserProfile::where('user_id', $this->owner->id)->first()->update(['privacy_settings' => 'private']);

        $this->assertSame('private', DB::table('user_profiles')->where('user_id', $this->owner->id)->value('privacy_settings'));
    }

    public function test_stats_use_the_real_friend_count_and_hide_visits_from_others(): void
    {
        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->owner->reference_code}/stats");

        $res->assertStatus(200);
        $this->assertSame(1, $res->json('data.friends'));
        $this->assertArrayNotHasKey('visits', $res->json('data'));

        $own = $this->actingAs($this->owner, 'api')->getJson('/api/profile/stats');
        $own->assertStatus(200);
        $this->assertArrayHasKey('visits', $own->json('data'));
    }

    public function test_duplicate_accepted_rows_do_not_inflate_the_friend_count(): void
    {
        // DB จริงมี 21 คู่ที่ถูกบันทึกว่ายอมรับซ้ำ 2 แถว (สองทิศทาง) — ต้องนับเป็นคน ไม่ใช่นับแถว
        DB::table('friendships')->insert([
            'sender_type' => User::class,
            'sender_id' => $this->friend->id,
            'recipient_type' => User::class,
            'recipient_id' => $this->owner->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->show($this->stranger);
        $res->assertStatus(200);
        $this->assertSame(1, $res->json('data.friends_count'));

        $stats = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->owner->reference_code}/stats");
        $stats->assertStatus(200);
        $this->assertSame(1, $stats->json('data.friends'));
    }
}
