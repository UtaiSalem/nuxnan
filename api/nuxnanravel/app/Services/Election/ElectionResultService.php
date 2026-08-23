<?php

namespace App\Services\Election;

use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionResult;
use App\Models\ElectionVoterReceipt;
use App\Models\MemberActivityLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ElectionResultService
{
    public function __construct(private ElectionService $elections, private ElectionBallotService $ballots, private ElectionStationService $stations) {}

    public function closeAndCount(Election $e, User $actor): array
    {
        return DB::transaction(function () use ($e, $actor) {
            $e = Election::whereKey($e->id)->lockForUpdate()->firstOrFail();
            if ($e->status !== Election::STATUS_VOTING) {
                throw new DomainException('ไม่สามารถปิดหีบเลือกตั้งจากสถานะ '.$e->status);
            }
            if ($e->published_at) {
                throw new DomainException('การเลือกตั้งนี้ประกาศผลแล้ว');
            }
            $this->stations->expireStale($e);
            $check = $this->ballots->verifyIntegrity($e);
            if (! $check['matches']) {
                throw new DomainException("จำนวนบัตรลงคะแนนไม่ตรงกัน: ballots={$check['ballots']}, cast_receipts={$check['cast_receipts']}");
            }
            $groups = ElectionBallot::query()->where('election_id', $e->id)->select('party_id', DB::raw('COUNT(*) as votes'))->groupBy('party_id')->get();
            ElectionResult::where('election_id', $e->id)->delete();
            $top = (int) ($groups->whereNotNull('party_id')->max('votes') ?? 0);
            $rank = 0;
            $previous = null;
            $position = 0;
            foreach ($groups->whereNotNull('party_id')->sortByDesc('votes') as $group) {
                $position++;
                if ($previous !== $group->votes) {
                    $rank = $position;
                    $previous = $group->votes;
                }
                ElectionResult::create(['election_id' => $e->id, 'party_id' => $group->party_id, 'votes' => $group->votes, 'rank' => $rank, 'is_winner' => (int) $group->votes === $top, 'published_at' => null, 'published_by' => null]);
            }
            if ($abstain = $groups->firstWhere('party_id', null)) {
                ElectionResult::create(['election_id' => $e->id, 'party_id' => null, 'votes' => $abstain->votes, 'rank' => null, 'is_winner' => false, 'published_at' => null, 'published_by' => null]);
            }
            $this->elections->transitionTo($e, Election::STATUS_CLOSED, $actor);
            MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $actor->id, 'action' => MemberActivityLog::ACTION_ELECTION_CLOSE_COUNT, 'description' => 'ปิดหีบและนับคะแนน', 'new_values' => ['election_id' => $e->id, 'ballots' => $check['ballots'], 'cast_receipts' => $check['cast_receipts']]]);

            return ['results' => ElectionResult::where('election_id', $e->id)->get()];
        });
    }

    public function publish(Election $e, User $actor): void
    {
        DB::transaction(function () use ($e, $actor) {
            $e = Election::whereKey($e->id)->lockForUpdate()->firstOrFail();
            if ($e->status !== Election::STATUS_CLOSED || $e->published_at || ! ElectionResult::where('election_id', $e->id)->exists()) {
                throw new DomainException('ไม่สามารถประกาศผลการเลือกตั้งได้');
            }
            $check = $this->ballots->verifyIntegrity($e);
            if (! $check['matches']) {
                throw new DomainException("จำนวนบัตรลงคะแนนไม่ตรงกัน: ballots={$check['ballots']}, cast_receipts={$check['cast_receipts']}");
            }
            ElectionResult::where('election_id', $e->id)->update(['published_at' => now(), 'published_by' => $actor->id]);
            $this->elections->transitionTo($e, Election::STATUS_PUBLISHED, $actor);
            $e->update(['published_at' => now()]);
            MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $actor->id, 'action' => MemberActivityLog::ACTION_ELECTION_PUBLISH, 'description' => 'ประกาศผลการเลือกตั้ง', 'new_values' => ['election_id' => $e->id]]);
        });
    }

    public function results(Election $e)
    {
        return ElectionResult::where('election_id', $e->id)->with('party')->get();
    }

    public function turnout(Election $e): array
    {
        $base = ElectionVoterReceipt::where('election_voter_receipts.election_id', $e->id)->where('status', 'cast');
        $total = $e->voters()->count();
        $issued = ElectionVoterReceipt::where('election_id', $e->id)->count();
        $voted = (clone $base)->count();

        return ['voted' => $voted, 'total' => $total, 'issued' => $issued, 'percentage' => $total ? round($voted * 100 / $total, 2) : 0, 'by_grade_level' => (clone $base)->join('election_voters', 'election_voters.id', '=', 'election_voter_receipts.election_voter_id')->select('election_voters.grade_level', DB::raw('COUNT(*) as voted'))->groupBy('election_voters.grade_level')->get(), 'by_station' => (clone $base)->leftJoin('election_stations', 'election_stations.id', '=', 'election_voter_receipts.station_id')->select('election_voter_receipts.station_id', 'election_stations.name as station_name', DB::raw('COUNT(*) as voted'))->groupBy('election_voter_receipts.station_id', 'election_stations.name')->get()];
    }
}
