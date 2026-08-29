<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AcademyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $member;

    protected $pending;

    protected $outsider;

    protected $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->pending = User::factory()->create();
        $this->outsider = User::factory()->create();

        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->member->id,
            'status' => 2,
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->pending->id,
            'status' => 1,
        ]);
    }

    private function applySettings(array $attributes): void
    {
        AcademySetting::updateOrCreate(
            ['academy_id' => $this->academy->id],
            $attributes
        );
        Cache::forget("academy_settings_{$this->academy->id}");
    }

    public function test_member_list_is_public_when_switch_is_on()
    {
        $this->applySettings(['privacy' => 'public', 'show_member_list' => true]);

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members");

        $response->assertStatus(200);
    }

    public function test_member_list_hides_from_outsiders_when_switch_is_off()
    {
        $this->applySettings(['privacy' => 'public', 'show_member_list' => false]);

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members")
            ->assertStatus(403)
            ->assertJsonPath('code', 'member_list_hidden');

        $this->actingAs($this->pending, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members")
            ->assertStatus(403);

        $this->actingAs($this->member, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members")
            ->assertStatus(200);

        $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members")
            ->assertStatus(200);
    }

    public function test_course_list_hides_from_outsiders_when_switch_is_off()
    {
        $this->applySettings(['privacy' => 'public', 'show_course_list' => false]);

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses")
            ->assertStatus(403)
            ->assertJsonPath('code', 'course_list_hidden');

        $this->actingAs($this->member, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses")
            ->assertStatus(200);

        $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses")
            ->assertStatus(200);
    }

    public function test_private_academy_blocks_content_endpoints_for_outsiders()
    {
        $this->applySettings(['privacy' => 'private', 'show_member_list' => true, 'show_course_list' => true]);

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/activities")
            ->assertStatus(403)
            ->assertJsonPath('code', 'academy_private');

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/members")
            ->assertStatus(403);

        $this->actingAs($this->member, 'api')
            ->getJson("/api/academies/{$this->academy->id}/activities")
            ->assertStatus(200);

        $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/activities")
            ->assertStatus(200);
    }

    public function test_private_academy_returns_trimmed_payload_to_outsiders()
    {
        $this->applySettings(['privacy' => 'private']);

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson("/api/academies/by-id/{$this->academy->id}");

        $response->assertStatus(200)
            ->assertJsonPath('academy.is_restricted', true);

        $payload = $response->json('academy');
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('address', $payload);
        $this->assertArrayNotHasKey('setting', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('join_mode', $payload);
    }

    public function test_owner_sees_full_payload_on_private_academy()
    {
        $this->applySettings(['privacy' => 'private']);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/by-id/{$this->academy->id}");

        $response->assertJsonPath('academy.is_restricted', false);
        $this->assertArrayHasKey('email', $response->json('academy'));
    }
}
