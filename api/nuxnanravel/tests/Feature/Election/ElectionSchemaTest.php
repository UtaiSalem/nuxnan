<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyPermission;
use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ElectionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ballot_is_structurally_secret(): void
    {
        $this->assertSame(['uuid', 'election_id', 'party_id'], Schema::getColumnListing('election_ballots'));
        $ballot = new ElectionBallot;
        $this->assertFalse($ballot->timestamps);
        $this->assertFalse($ballot->getIncrementing());
    }

    public function test_casting_two_ballots_generates_uuidv4_values(): void
    {
        [$election,$user] = $this->context();
        $a = ElectionBallot::create(['election_id' => $election->id]);
        $b = ElectionBallot::create(['election_id' => $election->id]);
        $this->assertNotSame($a->uuid, $b->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $a->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $b->uuid);
    }

    public function test_receipt_rejects_duplicate_voter_in_election(): void
    {
        [$election,$user] = $this->context();
        $station = ElectionStation::create(['election_id' => $election->id, 'name' => 'หน่วย 1']);
        $voter = ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => 'Voter', 'voter_type' => 'student']);
        $data = ['election_id' => $election->id, 'election_voter_id' => $voter->id, 'user_id' => $user->id, 'station_id' => $station->id, 'issued_by' => $user->id, 'issued_at' => now()];
        ElectionVoterReceipt::create($data);
        $this->expectException(QueryException::class);
        ElectionVoterReceipt::create($data);
    }

    public function test_parties_reject_duplicate_number(): void
    {
        [$election,$user] = $this->context();
        ElectionParty::create(['election_id' => $election->id, 'number' => 1, 'name' => 'One', 'applied_by' => $user->id]);
        $this->expectException(QueryException::class);
        ElectionParty::create(['election_id' => $election->id, 'number' => 1, 'name' => 'Two', 'applied_by' => $user->id]);
    }

    public function test_election_permissions_are_delegable(): void
    {
        $keys = array_column(AcademyPermission::getAllPermissions(), 'name');
        $this->assertEqualsCanonicalizing(['elections.view', 'elections.manage', 'elections.station'], array_values(array_intersect($keys, ['elections.view', 'elections.manage', 'elections.station'])));
        $this->assertContains('elections.view', AcademyPermission::departmentDelegableKeys());
        $this->assertContains('elections.manage', AcademyPermission::departmentDelegableKeys());
        $this->assertContains('elections.station', AcademyPermission::departmentDelegableKeys());
    }

    public function test_manage_is_not_non_delegable(): void
    {
        $this->assertSame([], AcademyPermission::nonDelegableDepartmentKeys(['elections.manage']));
    }

    private function context(): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $user->id]);

        return [Election::create(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $user->id]), $user];
    }
}
