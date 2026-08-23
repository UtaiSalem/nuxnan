<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ElectionRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_issue_limit_is_scoped_to_the_station_not_the_actor_or_ip(): void
    {
        [$academy, $actor, $election, $stationA, $stationB] = $this->context();
        $base = "/api/academies/{$academy->id}/elections/{$election->id}/stations";

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->actingAs($actor, 'api')
                ->postJson("$base/{$stationA->id}/issue", ['user_id' => 'invalid'])
                ->assertStatus(422);
        }

        $limited = $this->actingAs($actor, 'api')
            ->postJson("$base/{$stationA->id}/issue", ['user_id' => 'invalid']);
        $limited->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'ระบบกำลังรับคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
            ])
            ->assertJsonStructure(['retry_after']);
        $this->assertNotEmpty($limited->headers->get('Retry-After'));

        $this->actingAs($actor, 'api')
            ->postJson("$base/{$stationB->id}/issue", ['user_id' => 'invalid'])
            ->assertStatus(422);
    }

    public function test_cast_limit_does_not_consume_issue_quota(): void
    {
        [$academy, $actor, $election, $stationA] = $this->context();
        $base = "/api/academies/{$academy->id}/elections";
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->actingAs($actor, 'api')->postJson("$base/{$election->id}/cast", ['ballot_token' => 'invalid'])->assertStatus(422);
        }

        $this->actingAs($actor, 'api')->postJson("$base/{$election->id}/stations/{$stationA->id}/issue", ['user_id' => 'invalid'])->assertStatus(422);
    }

    private function context(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => uniqid(),
            'display_name_th' => 'Test',
            'permissions' => ['elections.station'],
        ]);
        $actor = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $actor->id,
            'academy_role_id' => $role->id,
            'status' => 2,
            'member_code' => 'ACTOR',
        ]);
        $election = Election::create([
            'academy_id' => $academy->id,
            'title' => 'Election',
            'created_by' => $owner->id,
            'status' => 'voting',
            'voter_roll_locked_at' => now(),
        ]);
        $stationA = ElectionStation::create(['election_id' => $election->id, 'name' => 'A']);
        $stationB = ElectionStation::create(['election_id' => $election->id, 'name' => 'B']);

        return [$academy, $actor, $election, $stationA, $stationB];
    }
}
