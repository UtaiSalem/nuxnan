<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Election\StoreElectionStationRequest;
use App\Http\Requests\Election\UpdateElectionStationRequest;
use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionStation;
use App\Models\ElectionVoterReceipt;
use App\Services\Election\ElectionStationService;
use DomainException;
use Illuminate\Http\Request;

class ElectionStationController extends Controller
{
    public function __construct(private ElectionStationService $service) {}

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

    public function open(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->open($this->station($a, $e, $s), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function close(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->close($this->station($a, $e, $s), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function lookup(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->lookup($this->station($a, $e, $s), $r->string('identifier')->toString())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function search(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        return response()->json(['success' => true, 'data' => $this->service->searchByName($this->station($a, $e, $s), $r->string('q')->toString())]);
    }

    public function issue(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->issue($this->station($a, $e, $s), (int) $r->input('user_id'), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function void(Academy $a, Election $e, ElectionStation $s, Request $r)
    {
        try {
            $this->station($a, $e, $s);
            $receipt = ElectionVoterReceipt::whereKey($r->input('receipt_id'))->where('election_id', $e->id)->firstOrFail();

            return response()->json(['success' => true, 'data' => $this->service->void($receipt, $r->input('reason', ''), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function progress(Academy $a, Election $e, ElectionStation $s)
    {
        $s = $this->station($a, $e, $s);

        return response()->json(['success' => true, 'data' => ['issued' => $s->receipts()->where('status', 'issued')->count(), 'cast' => $s->receipts()->where('status', 'cast')->count(), 'remaining' => $e->voters()->count() - $e->receipts()->where('status', 'cast')->count()]]);
    }
}
