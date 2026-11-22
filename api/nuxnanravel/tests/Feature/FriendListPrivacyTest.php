<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FriendListPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = [], array $profile = []): User
    {
        $u = User::factory()->create($attrs);
        UserProfile::create(array_merge(['user_id' => $u->id], $profile));

        return $u;
    }

    private function befriend(User $a, User $b): void
    {
        $a->befriend($b);
        $b->acceptFriendRequest($a);
    }

    private function findItem(array $data, int $id): ?array
    {
        foreach ($data as $item) {
            if ((int) $item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    public function test_friend_request_is_blocked_when_the_recipient_disallows_requests(): void
    {
        $r = $this->makeUser([], ['allow_friend_requests' => false]);
        $s = $this->makeUser();

        $res = $this->actingAs($s, 'api')->postJson("/api/friends/{$r->id}");

        $res->assertStatus(403);
        $this->assertSame('FRIEND_REQUESTS_DISABLED', $res->json('code'));
        $this->assertSame(0, DB::table('friendships')->count());
    }

    public function test_cannot_send_a_friend_request_to_yourself(): void
    {
        $me = $this->makeUser();

        $res = $this->actingAs($me, 'api')->postJson("/api/friends/{$me->id}");

        $res->assertStatus(422);
        $this->assertSame('CANNOT_FRIEND_SELF', $res->json('code'));
    }

    public function test_duplicate_friend_request_returns_409(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->befriend($a, $b);

        $res = $this->actingAs($a, 'api')->postJson("/api/friends/{$b->id}");
        $res->assertStatus(409);
        $this->assertSame('FRIEND_REQUEST_NOT_ALLOWED', $res->json('code'));

        $c = $this->makeUser();
        $d = $this->makeUser();
        $c->befriend($d);

        $res = $this->actingAs($c, 'api')->postJson("/api/friends/{$d->id}");
        $res->assertStatus(409);
        $this->assertSame('FRIEND_REQUEST_NOT_ALLOWED', $res->json('code'));
    }

    public function test_friend_request_response_has_no_private_columns(): void
    {
        $r = $this->makeUser(['phone_number' => '0812345678', 'pp' => 900, 'wallet' => 100.25]);
        $s = $this->makeUser();

        $res = $this->actingAs($s, 'api')->postJson("/api/friends/{$r->id}");

        $res->assertStatus(200);
        $user = $res->json('user');
        $this->assertIsArray($user);

        foreach (['email', 'phone', 'phone_number', 'wallet', 'personal_code', 'pp', 'points'] as $key) {
            $this->assertArrayNotHasKey($key, $user, "friend request response leaks [{$key}]");
        }
    }

    public function test_friends_list_follows_the_owner_privacy(): void
    {
        $owner = $this->makeUser([], ['privacy_settings' => 'private']);
        $friend = $this->makeUser();
        $stranger = $this->makeUser();
        $this->befriend($owner, $friend);

        $url = "/api/users/{$owner->reference_code}/friends";

        $res = $this->actingAs($stranger, 'api')->getJson($url);
        $res->assertStatus(403);
        $this->assertSame('FRIENDS_LIST_PRIVATE', $res->json('code'));
        $this->actingAs($friend, 'api')->getJson($url)->assertStatus(403);
        $this->actingAs($owner, 'api')->getJson($url)->assertStatus(200);

        UserProfile::where('user_id', $owner->id)->update(['privacy_settings' => 'friends']);

        $this->actingAs($friend, 'api')->getJson($url)->assertStatus(200);
        $this->actingAs($stranger, 'api')->getJson($url)->assertStatus(403);

        UserProfile::where('user_id', $owner->id)->update(['privacy_settings' => 'public']);

        $res = $this->actingAs($stranger, 'api')->getJson($url);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_friends_list_is_paginated_by_20(): void
    {
        $owner = $this->makeUser([], ['privacy_settings' => 'public']);
        $stranger = $this->makeUser();

        for ($i = 0; $i < 25; $i++) {
            $this->befriend($owner, $this->makeUser());
        }

        $url = "/api/users/{$owner->reference_code}/friends";

        $res = $this->actingAs($stranger, 'api')->getJson($url);
        $res->assertStatus(200);
        $this->assertCount(20, $res->json('data'));
        $this->assertSame(25, $res->json('meta.total'));
        $this->assertSame(2, $res->json('meta.last_page'));

        $res = $this->actingAs($stranger, 'api')->getJson($url.'?page=2');
        $res->assertStatus(200);
        $this->assertCount(5, $res->json('data'));
    }

    public function test_friend_list_items_have_no_fake_presence_or_real_names(): void
    {
        $owner = $this->makeUser([], ['privacy_settings' => 'public']);
        $f = $this->makeUser([], ['first_name' => 'Secret', 'last_name' => 'Realname']);
        $stranger = $this->makeUser();
        $this->befriend($owner, $f);

        $res = $this->actingAs($stranger, 'api')->getJson("/api/users/{$owner->reference_code}/friends");
        $res->assertStatus(200);

        $item = $this->findItem($res->json('data'), (int) $f->id);
        $this->assertIsArray($item);
        $this->assertArrayNotHasKey('is_online', $item);
        $this->assertArrayNotHasKey('last_seen', $item);
        $this->assertSame($f->name, $item['full_name']);
        $this->assertStringNotContainsString('Realname', $res->getContent());
        $this->assertSame((int) ($f->fresh()->xp_level ?? 1), $item['level']);
    }

    public function test_mutual_friend_counts_are_correct(): void
    {
        $t = $this->makeUser([], ['privacy_settings' => 'public']);
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();
        $v = $this->makeUser();

        $this->befriend($t, $a);
        $this->befriend($t, $b);
        $this->befriend($t, $c);
        $this->befriend($v, $a);
        $this->befriend($v, $b);
        $this->befriend($a, $b);

        $res = $this->actingAs($v, 'api')->getJson("/api/users/{$t->reference_code}/friends");
        $res->assertStatus(200);
        $data = $res->json('data');

        $this->assertSame(1, $this->findItem($data, (int) $a->id)['mutual_friends_count']);
        $this->assertSame(1, $this->findItem($data, (int) $b->id)['mutual_friends_count']);
        $this->assertSame(0, $this->findItem($data, (int) $c->id)['mutual_friends_count']);
    }

    public function test_suggestions_and_pending_requests_do_not_expose_emails(): void
    {
        $me = $this->makeUser();
        $someone = $this->makeUser();
        $this->makeUser();
        $this->makeUser();

        $res = $this->actingAs($me, 'api')->getJson('/api/friends/suggestions');
        $res->assertStatus(200);
        $users = $res->json('users');
        $this->assertNotEmpty($users);

        foreach ($users as $user) {
            $this->assertArrayNotHasKey('email', $user);
        }

        $someone->befriend($me);

        $res = $this->actingAs($me, 'api')->getJson('/api/friends/pending');
        $res->assertStatus(200);
        $sender = $res->json('requests.0.sender');
        $this->assertIsArray($sender);
        $this->assertArrayNotHasKey('email', $sender);
    }

    public function test_friends_resolve_by_id_and_personal_code_but_not_display_name(): void
    {
        $owner = $this->makeUser(
            ['name' => 'Same Display Name P1B', 'username' => 'p1b_owner_handle'],
            ['privacy_settings' => 'public']
        );
        $viewer = $this->makeUser();

        $this->actingAs($viewer, 'api')->getJson("/api/users/{$owner->id}/friends")->assertStatus(200);
        $this->actingAs($viewer, 'api')->getJson("/api/users/{$owner->personal_code}/friends")->assertStatus(200);
        $this->actingAs($viewer, 'api')->getJson('/api/users/p1b_owner_handle/friends')->assertStatus(200);
        $this->actingAs($viewer, 'api')
            ->getJson('/api/users/'.rawurlencode('Same Display Name P1B').'/friends')
            ->assertStatus(404);
    }

    public function test_user_videos_do_not_crash_for_signed_in_friends(): void
    {
        $owner = $this->makeUser();
        $friend = $this->makeUser();
        $this->befriend($owner, $friend);

        $this->actingAs($friend, 'api')
            ->getJson("/api/users/{$owner->reference_code}/videos")
            ->assertStatus(200);
    }

    public function test_duplicate_accepted_rows_do_not_double_count_mutual_friends(): void
    {
        $t = $this->makeUser([], ['privacy_settings' => 'public']);
        $a = $this->makeUser();
        $b = $this->makeUser();
        $v = $this->makeUser();

        $this->befriend($t, $a);
        $this->befriend($v, $b);
        $this->befriend($a, $b);

        // a–b ถูกบันทึกว่ายอมรับซ้ำอีกแถว (อีกทิศทาง) แบบที่พบใน DB จริง
        DB::table('friendships')->insert([
            'sender_type' => User::class,
            'sender_id' => $b->id,
            'recipient_type' => User::class,
            'recipient_id' => $a->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->actingAs($v, 'api')->getJson("/api/users/{$t->reference_code}/friends");
        $res->assertStatus(200);

        $this->assertSame(1, $this->findItem($res->json('data'), (int) $a->id)['mutual_friends_count']);
    }
}
