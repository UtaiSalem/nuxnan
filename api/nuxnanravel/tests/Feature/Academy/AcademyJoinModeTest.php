<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AcademyJoinModeTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $outsider;

    protected $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->outsider = User::factory()->create();
        $this->outsider->forceFill(['pp' => 1000000])->save();

        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
            'membership_fees_points' => 0,
            'total_students' => 0,
        ]);
    }

    private function setJoinMode(string $mode): void
    {
        AcademySetting::updateOrCreate(
            ['academy_id' => $this->academy->id],
            ['privacy' => 'public', 'join_mode' => $mode]
        );
        Cache::forget("academy_settings_{$this->academy->id}");
    }

    public function test_open_mode_approves_immediately_and_increments_total_students()
    {
        $this->setJoinMode('open');

        $response = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response->assertStatus(200)
            ->assertJsonPath('memberStatus', 2);

        $this->assertDatabaseHas('academy_members', [
            'academy_id' => $this->academy->id,
            'user_id' => $this->outsider->id,
            'status' => 2,
        ]);

        $this->academy->refresh();
        $this->assertEquals(1, $this->academy->total_students);
    }

    public function test_approval_mode_creates_pending_row_and_does_not_touch_total_students()
    {
        $this->setJoinMode('approval');

        $response = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response->assertStatus(200)
            ->assertJsonPath('memberStatus', 1);

        $this->assertDatabaseHas('academy_members', [
            'academy_id' => $this->academy->id,
            'user_id' => $this->outsider->id,
            'status' => 1,
        ]);

        $this->academy->refresh();
        $this->assertEquals(0, $this->academy->total_students);
    }

    public function test_invite_only_blocks_join_request_entirely()
    {
        $this->setJoinMode('invite_only');

        $response = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response->assertStatus(403)
            ->assertJsonPath('code', 'invite_only');

        $this->assertDatabaseMissing('academy_members', [
            'academy_id' => $this->academy->id,
            'user_id' => $this->outsider->id,
        ]);

        $this->academy->refresh();
        $this->assertEquals(0, $this->academy->total_students);
    }

    public function test_duplicate_join_request_returns_conflict_not_server_error()
    {
        $this->setJoinMode('approval');

        $response1 = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response1->assertStatus(200);

        $response2 = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response2->assertStatus(409)
            ->assertJsonPath('code', 'already_requested');

        $this->assertEquals(1, AcademyMember::where([
            'academy_id' => $this->academy->id,
            'user_id' => $this->outsider->id,
        ])->count());
    }

    public function test_unknown_join_mode_falls_back_to_approval()
    {
        AcademySetting::updateOrCreate(
            ['academy_id' => $this->academy->id],
            ['privacy' => 'public', 'join_mode' => 'garbage']
        );
        Cache::forget("academy_settings_{$this->academy->id}");

        $response = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/members");

        $response->assertStatus(200)
            ->assertJsonPath('memberStatus', 1);
    }
}
