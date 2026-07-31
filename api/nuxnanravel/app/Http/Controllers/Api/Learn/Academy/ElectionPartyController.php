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

    private function find(Academy $a, Election $e, ElectionParty $p): ElectionParty
    {
        abort_if($e->academy_id !== $a->id || $p->election_id !== $e->id, 404);

        return $p;
    }

    private function fail(DomainException $e)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }

    public function store(StoreElectionPartyRequest $r, Academy $a, Election $e)
    {
        abort_if($e->academy_id !== $a->id, 404);
        try {
            return response()->json(['success' => true, 'data' => $this->service->apply($e, $r->validated(), $r->user())], 201);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function update(UpdateElectionPartyRequest $r, Academy $a, Election $e, ElectionParty $p)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->update($this->find($a, $e, $p), $r->validated(), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function withdraw(Request $r, Academy $a, Election $e, ElectionParty $p)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->withdraw($this->find($a, $e, $p), $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function index(Academy $a, Election $e)
    {
        abort_if($e->academy_id !== $a->id, 404);

        return response()->json(['success' => true, 'data' => $e->parties()->with('members.user')->orderByRaw('number IS NULL')->orderBy('number')->orderBy('created_at')->get()]);
    }

    public function approve(ApproveElectionPartyRequest $r, Academy $a, Election $e, ElectionParty $p)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->approve($this->find($a, $e, $p), $r->validated()['number'] ?? null, $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }

    public function reject(RejectElectionPartyRequest $r, Academy $a, Election $e, ElectionParty $p)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->reject($this->find($a, $e, $p), $r->validated()['review_note'], $r->user())]);
        } catch (DomainException $x) {
            return $this->fail($x);
        }
    }
}
