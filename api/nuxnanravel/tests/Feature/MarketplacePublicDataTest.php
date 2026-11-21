<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplacePublicDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Academy $academy;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'email' => 'owner-secret@example.com',
            'phone_number' => '0833333333',
            'wallet' => 777,
        ]);
        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
            'director' => (string) $this->owner->id,
        ]);
        $this->course = Course::factory()->create([
            'user_id' => $this->owner->id,
            'academy_id' => $this->academy->id,
            'is_for_marketplace' => true,
        ]);
    }

    public function test_guest_marketplace_does_not_expose_owner_private_data(): void
    {
        $res = $this->getJson('/api/courses/marketplace');

        $res->assertOk();
        $user = $res->json('data.0.user');
        $this->assertIsArray($user);

        foreach (['email', 'phone', 'phone_number', 'wallet', 'personal_code', 'points'] as $key) {
            $this->assertArrayNotHasKey($key, $user);
        }

        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('avatar', $user);
        $this->assertStringNotContainsString('owner-secret@example.com', $res->getContent());
        $this->assertStringNotContainsString('0833333333', $res->getContent());
    }

    public function test_signed_in_stranger_marketplace_does_not_expose_owner_private_data(): void
    {
        $stranger = User::factory()->create();
        $res = $this->actingAs($stranger, 'api')->getJson('/api/courses/marketplace');

        $res->assertOk();
        $this->assertStringNotContainsString('owner-secret@example.com', $res->getContent());
        $this->assertStringNotContainsString('0833333333', $res->getContent());

        foreach (['creater', 'director'] as $role) {
            $user = $res->json('data.0.academy.'.$role);

            if ($user !== null) {
                foreach (['email', 'phone', 'phone_number', 'wallet'] as $key) {
                    $this->assertArrayNotHasKey($key, $user);
                }
            }
        }
    }
}
