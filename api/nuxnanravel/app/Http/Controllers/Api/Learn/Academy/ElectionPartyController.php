<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Election\ApproveElectionPartyRequest;
use App\Http\Requests\Election\RejectElectionPartyRequest;
use App\Http\Requests\Election\StoreElectionPartyRequest;
use App\Http\Requests\Election\UpdateElectionPartyRequest;
use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Services\Election\ElectionPartyService;
use DomainException;
use Illuminate\Http\Request;

class ElectionPartyController extends Controller
{
    public function __construct(private ElectionPartyService $service) {}

    private function find(Academy $academy, Election $election, ElectionParty $party): ElectionParty
    {
        abort_if($election->academy_id !== $academy->id || $party->election_id !== $election->id, 404);

        return $party;
    }

    private function fail(DomainException $e)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }

    public function store(StoreElectionPartyRequest $r, Academy $academy, Election $election)
    {
        abort_if($election->academy_id !== $academy->id, 404);
        try {
            return response()->json(['success' => true, 'data' => $this->service->apply($election, $r->validated(), $r->user())], 201);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function update(UpdateElectionPartyRequest $r, Academy $academy, Election $election, ElectionParty $party)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->update($this->find($academy, $election, $party), $r->validated(), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function withdraw(Request $r, Academy $academy, Election $election, ElectionParty $party)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->withdraw($this->find($academy, $election, $party), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function index(Academy $academy, Election $election)
    {
        abort_if($election->academy_id !== $academy->id, 404);

        return response()->json(['success' => true, 'data' => $election->parties()->with('members.user')->orderByRaw('number IS NULL')->orderBy('number')->orderBy('created_at')->get()]);
    }

    public function approve(ApproveElectionPartyRequest $r, Academy $academy, Election $election, ElectionParty $party)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->approve($this->find($academy, $election, $party), $r->validated()['number'] ?? null, $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function reject(RejectElectionPartyRequest $r, Academy $academy, Election $election, ElectionParty $party)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->reject($this->find($academy, $election, $party), $r->validated()['review_note'], $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }
}
