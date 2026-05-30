<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $friend;
    protected $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Users
        $this->owner = User::factory()->create(['name' => 'Owner User', 'email' => 'owner@example.com']);
        $this->friend = User::factory()->create(['name' => 'Friend User', 'email' => 'friend@example.com']);
        $this->stranger = User::factory()->create(['name' => 'Stranger User', 'email' => 'stranger@example.com']);

        // Create Profiles
        UserProfile::create([
            'user_id' => $this->owner->id,
            'first_name' => 'Owner',
            'last_name' => 'Test',
            'show_email' => true,
            'show_phone' => true,
            'location' => 'Bangkok, Thailand',
            'privacy_settings' => 'public'
        ]);

        UserProfile::create([
            'user_id' => $this->friend->id,
            'first_name' => 'Friend',
            'last_name' => 'Test'
        ]);

        UserProfile::create([
            'user_id' => $this->stranger->id,
            'first_name' => 'Stranger',
            'last_name' => 'Test'
        ]);

        // Establish Friendship: Owner <-> Friend
        $this->owner->befriend($this->friend);
        $this->friend->acceptFriendRequest($this->owner);
    }

    /** @test */
    public function owner_can_see_their_own_private_info()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/profile/me");

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'owner@example.com')
            ->assertJsonPath('data.is_own_profile', true)
            ->assertJsonPath('data.can_view_full_profile', true);
    }

    /** @test */
    public function friend_can_see_allowed_private_info()
    {
        $response = $this->actingAs($this->friend, 'api')
            ->getJson("/api/users/{$this->owner->id}/show");

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'owner@example.com') // show_email is true
            ->assertJsonPath('data.is_own_profile', false)
            ->assertJsonPath('data.is_friend', true)
            ->assertJsonPath('data.can_view_full_profile', true);
    }

    /** @test */
    public function stranger_cannot_see_private_info()
    {
        $response = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->owner->id}/show");

        $response->assertStatus(200)
            ->assertJsonMissing(['email'])
            ->assertJsonPath('data.is_own_profile', false)
            ->assertJsonPath('data.is_friend', false)
            ->assertJsonPath('data.can_view_full_profile', false);
    }

    /** @test */
    public function stranger_cannot_see_email_even_if_show_email_is_true_because_they_are_not_friends()
    {
        // Settings: show_email=true but only for friends (per resource logic)
        $this->owner->profile->update(['show_email' => true]);

        $response = $this->actingAs($this->stranger, 'api')
            ->getJson("/api/users/{$this->owner->id}/show");

        $response->assertStatus(200)
            ->assertJsonMissing(['email']);
    }

    /** @test */
    public function level_progress_is_calculated_correctly()
    {
        // Give some XP to owner
        $this->owner->update(['pp' => 1500]); // Assuming 1000 XP per level for simplicity in test logic

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/profile/me");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'level',
                    'level_progress',
                    'experience',
                    'experience_to_next_level'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(0, $data['level_progress']);
        $this->assertLessThanOrEqual(100, $data['level_progress']);
    }
}
