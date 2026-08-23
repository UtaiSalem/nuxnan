<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Election\StoreElectionStationRequest;
use App\Http\Requests\Election\UpdateElectionStationRequest;
use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionStation;
use App\Models\ElectionVoterReceipt;
use App\Services\Election\ElectionBallotService;
use App\Services\Election\ElectionStationService;
use DomainException;
use Illuminate\Http\Request;

class ElectionStationController extends Controller
{
    public function __construct(private ElectionStationService $service) {}

    public function cast(Academy $academy, Election $election, Request $r, ElectionBallotService $ballots)
    {
        abort_if($election->academy_id !== $academy->id, 404);
        try {
            return response()->json(['success' => true, 'data' => $ballots->cast($election, $r->string('ballot_token')->toString(), $r->has('party_id') && $r->input('party_id') !== null ? (int) $r->input('party_id') : null, $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    private function station(Academy $academy, Election $election, ElectionStation $station): ElectionStation
    {
        abort_if($election->academy_id !== $academy->id || $station->election_id !== $election->id, 404);

        return $station;
    }

    private function fail(DomainException $e)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }

    public function store(StoreElectionStationRequest $request, Academy $academy, Election $election)
    {
        abort_if($election->academy_id !== $academy->id, 404);

        return response()->json(['success' => true, 'data' => $election->stations()->create($request->validated())], 201);
    }

    public function update(UpdateElectionStationRequest $request, Academy $academy, Election $election, ElectionStation $station)
    {
        $s = $this->station($academy, $election, $station);
        $s->update($request->validated());

        return response()->json(['success' => true, 'data' => $s->fresh()]);
    }

    public function destroy(Academy $academy, Election $election, ElectionStation $station)
    {
        $s = $this->station($academy, $election, $station);
        $s->delete();

        return response()->json(['success' => true]);
    }

    public function open(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->open($this->station($academy, $election, $station), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function close(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->close($this->station($academy, $election, $station), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function lookup(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->lookup(
                $this->station($academy, $election, $station),
                $r->string('identifier')->toString(),
                $r->filled('user_id') ? (int) $r->input('user_id') : null,
                $r->filled('member_code') ? $r->string('member_code')->toString() : null,
            )]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function search(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        return response()->json(['success' => true, 'data' => $this->service->searchByName($this->station($academy, $election, $station), $r->string('q')->toString())]);
    }

    public function issue(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->issue($this->station($academy, $election, $station), (int) $r->input('user_id'), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function void(Academy $academy, Election $election, ElectionStation $station, Request $r)
    {
        try {
            $this->station($academy, $election, $station);
            $receipt = ElectionVoterReceipt::whereKey($r->input('receipt_id'))->where('election_id', $election->id)->firstOrFail();

            return response()->json(['success' => true, 'data' => $this->service->void($receipt, $r->input('reason', ''), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function progress(Academy $academy, Election $election, ElectionStation $station)
    {
        $s = $this->station($academy, $election, $station);

        return response()->json(['success' => true, 'data' => [
            'name' => $s->name,
            'is_open' => (bool) $s->is_open,
            'location' => $s->location,
            'issued' => $s->receipts()->where('status', 'issued')->count(),
            'cast' => $s->receipts()->where('status', 'cast')->count(),
            'remaining' => $election->voters()->count() - $election->receipts()->where('status', 'cast')->count(),
        ]]);
    }
}
