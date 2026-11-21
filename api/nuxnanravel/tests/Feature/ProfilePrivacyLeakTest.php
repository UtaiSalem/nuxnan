<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfilePrivacyLeakTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_USER_KEYS = ['email', 'phone', 'phone_number', 'personal_code', 'pp', 'points',
        'wallet', 'locked_balance', 'suggester_code', 'freeze_reason', 'google_id', 'facebook_id',
        'two_factor_confirmed_at', 'deletion_reason'];

    protected $owner;

    protected $friend;

    protected $stranger;

    protected $ownerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['phone_number' => '0812345678', 'pp' => 1500, 'wallet' => 250.50]);
        $this->friend = User::factory()->create(['phone_number' => '0899999999']);
        $this->stranger = User::factory()->create();

        $this->ownerProfile = UserProfile::create([
            'user_id' => $this->owner->id,
            'first_name' => 'Owner',
            'last_name' => 'Secret',
            'gender' => 'male',
            'website' => 'https://owner.example.com',
            'interests' => ['chess'],
            'skills' => ['math'],
            'privacy_settings' => 'public',
            'last_login' => now(),
        ]);
        UserProfile::create(['user_id' => $this->friend->id]);
        UserProfile::create(['user_id' => $this->stranger->id]);

        $this->owner->befriend($this->friend);
        $this->friend->acceptFriendRequest($this->owner);
    }

    private function assertNoPrivateKeys(?array $user, string $where): void
    {
        $this->assertIsArray($user, "{$where}: expected a user object");

        foreach (self::PRIVATE_USER_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $user, "{$where} leaks private key [{$key}]");
        }
    }

    private function ref(): string
    {
        return $this->owner->reference_code;
    }

    public function test_activities_user_key_has_no_private_columns(): void
    {
        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->ref()}/activities");

        $res->assertStatus(200);

        $user = $res->json('user');
        $this->assertNoPrivateKeys($user, 'activities.user');
        $this->assertSame($this->owner->id, $user['id']);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('avatar', $user);
        $this->assertArrayHasKey('reference_code', $user);
    }

    public function test_activities_items_do_not_embed_private_columns(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'content' => 'public post',
            'privacy_settings' => 3,
            'status' => 1,
        ]);

        Activity::create([
            'user_id' => $this->owner->id,
            'activity_type' => 'createpost',
            'activityable_type' => Post::class,
            'activityable_id' => $post->id,
        ]);

        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->ref()}/activities");

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('activities'));

        $this->assertNoPrivateKeys($res->json('activities.0.action_by'), 'activities.0.action_by');
        $this->assertNoPrivateKeys($res->json('activities.0.target_resource.author'), 'activities.0.target_resource.author');
    }

    public function test_show_hides_balances_from_stranger_and_friend(): void
    {
        foreach (['stranger' => $this->stranger, 'friend' => $this->friend] as $label => $viewer) {
            $res = $this->actingAs($viewer, 'api')
                ->getJson("/api/users/{$this->ref()}/show");

            $res->assertStatus(200);

            $data = $res->json('data');
            foreach (['points', 'wallet', 'last_login', 'personal_code', 'profile_completion'] as $key) {
                $this->assertArrayNotHasKey($key, $data, "{$label} can see [{$key}] on show");
            }
        }
    }

    public function test_owner_still_gets_balances_on_profile_me(): void
    {
        $res = $this->actingAs($this->owner, 'api')->getJson('/api/profile/me');

        $res->assertStatus(200);
        $this->assertEquals(1500, $res->json('data.points'));
        $this->assertEquals(250.5, (float) $res->json('data.wallet'));

        $data = $res->json('data');
        $this->assertArrayHasKey('last_login', $data);
        $this->assertArrayHasKey('profile_completion', $data);
    }

    public function test_about_fields_follow_friends_privacy(): void
    {
        $this->ownerProfile->update(['privacy_settings' => 'friends']);

        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->ref()}/show");

        $res->assertStatus(200);
        $data = $res->json('data');
        foreach (['first_name', 'last_name', 'gender', 'website', 'interests', 'skills'] as $key) {
            $this->assertArrayNotHasKey($key, $data, "stranger can see [{$key}] on a friends-only profile");
        }
        $this->assertSame($this->owner->name, $data['full_name']);
        $this->assertSame([], $data['social_media_links']);

        $friendRes = $this->actingAs($this->friend, 'api')
            ->getJson("/api/users/{$this->ref()}/show");

        $friendRes->assertStatus(200);
        $this->assertSame('Owner', $friendRes->json('data.first_name'));
    }

    public function test_about_fields_follow_private_privacy_stored_as_plain_string(): void
    {
        DB::table('user_profiles')->where('user_id', $this->owner->id)->update(['privacy_settings' => 'private']);

        $friendRes = $this->actingAs($this->friend, 'api')
            ->getJson("/api/users/{$this->ref()}/show");

        $friendRes->assertStatus(200);
        $this->assertArrayNotHasKey('first_name', $friendRes->json('data'));

        $ownerRes = $this->actingAs($this->owner, 'api')->getJson('/api/profile/me');

        $ownerRes->assertStatus(200);
        $this->assertSame('Owner', $ownerRes->json('data.first_name'));
    }

    public function test_public_profile_still_shows_about_fields(): void
    {
        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->ref()}/show");

        $res->assertStatus(200);
        $this->assertSame('Owner', $res->json('data.first_name'));
        $this->assertSame('male', $res->json('data.gender'));
    }

    public function test_stats_hide_balances_from_others(): void
    {
        $res = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->ref()}/stats");

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertArrayNotHasKey('points', $data);
        $this->assertArrayNotHasKey('wallet', $data);
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('friends', $data);
        $this->assertArrayNotHasKey('visits', $data);

        $ownRes = $this->actingAs($this->owner, 'api')->getJson('/api/profile/stats');

        $ownRes->assertStatus(200);
        $this->assertEquals(1500, $ownRes->json('data.points'));
        $this->assertArrayHasKey('wallet', $ownRes->json('data'));

        $ownByRefRes = $this->actingAs($this->owner, 'api')
            ->getJson("/api/users/{$this->ref()}/stats");

        $ownByRefRes->assertStatus(200);
        $this->assertArrayHasKey('points', $ownByRefRes->json('data'));
    }
}
