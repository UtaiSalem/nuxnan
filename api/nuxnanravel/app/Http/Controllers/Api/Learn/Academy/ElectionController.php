<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Election\StoreElectionRequest;
use App\Http\Requests\Election\TransitionElectionStatusRequest;
use App\Http\Requests\Election\UpdateElectionRequest;
use App\Models\Academy;
use App\Models\Election;
use App\Models\MemberActivityLog;
use App\Services\Election\ElectionResultService;
use App\Services\Election\ElectionService;
use DomainException;
use Illuminate\Http\Request;

class ElectionController extends Controller
{
    public function __construct(private ElectionService $service, private ElectionResultService $results) {}

    public function closeAndCount(Request $request, Academy $academy, Election $election)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->results->closeAndCount($this->find($academy, $election), $request->user())]);
        } catch (DomainException $e) {
            return $this->fail($e);
        }
    }

    public function publish(Request $request, Academy $academy, Election $election)
    {
        try {
            $this->results->publish($this->find($academy, $election), $request->user());

            return response()->json(['success' => true]);
        } catch (DomainException $e) {
            return $this->fail($e);
        }
    }

    public function results(Academy $academy, Election $election)
    {
        $e = $this->find($academy, $election);
        abort_unless($e->published_at, 404);

        return response()->json(['success' => true, 'data' => $this->results->results($e)]);
    }

    public function turnout(Academy $academy, Election $election)
    {
        return response()->json(['success' => true, 'data' => $this->results->turnout($this->find($academy, $election))]);
    }

    private function find(Academy $academy, Election $election): Election
    {
        abort_if($election->academy_id !== $academy->id, 404);

        return $election;
    }

    private function fail(DomainException $e)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }

    public function index(Request $request, Academy $academy)
    {
        $q = $academy->elections()->with('academicYear')->withCount(['parties as approved_parties_count' => fn ($x) => $x->where('status', 'approved'), 'voters as voters_count', 'receipts as receipts_cast_count' => fn ($x) => $x->where('status', 'cast')]);
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        } if ($request->filled('academic_year_id')) {
            $q->where('academic_year_id', $request->academic_year_id);
        }

        return response()->json(['success' => true, 'data' => $q->latest()->paginate($request->integer('per_page', 15))]);
    }

    public function store(StoreElectionRequest $request, Academy $academy)
    {
        return response()->json(['success' => true, 'data' => $this->service->create($request->validated(), $request->user(), $academy)], 201);
    }

    public function show(Academy $academy, Election $election)
    {
        return response()->json(['success' => true, 'data' => $this->find($academy, $election)->load(['academicYear'])->loadCount(['parties as approved_parties_count' => fn ($x) => $x->where('status', 'approved'), 'voters as voters_count', 'receipts as receipts_cast_count' => fn ($x) => $x->where('status', 'cast')])]);
    }

    public function update(UpdateElectionRequest $request, Academy $academy, Election $election)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->update($this->find($academy, $election), $request->validated(), $request->user())]);
        } catch (DomainException $e) {
            return $this->fail($e);
        }
    }

    public function destroy(Request $request, Academy $academy, Election $election)
    {
        try {
            $this->service->delete($this->find($academy, $election), $request->user());

            return response()->json(['success' => true]);
        } catch (DomainException $e) {
            return $this->fail($e);
        }
    }

    public function transitionStatus(TransitionElectionStatusRequest $request, Academy $academy, Election $election)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->transitionTo($this->find($academy, $election), $request->validated()['status'], $request->user())]);
        } catch (DomainException $e) {
            return $this->fail($e);
        }
    }

    public function auditLog(Academy $academy, Election $election)
    {
        $e = $this->find($academy, $election);

        return response()->json(['success' => true, 'data' => MemberActivityLog::where('academy_id', $academy->id)->whereIn('action', MemberActivityLog::electionActions())->where(function ($q) use ($e) {
            $q->where('new_values->election_id', $e->id)->orWhere('old_values->election_id', $e->id);
        })->latest()->paginate(15)]);
    }
}
