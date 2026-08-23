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
use App\Models\User;
use App\Services\Election\ElectionPartyService;
use App\Services\Election\ElectionVoterRollService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionPartyController extends Controller
{
    public function __construct(private ElectionPartyService $service, private ElectionVoterRollService $voterRoll) {}

    public function candidates(Request $r, Academy $academy, Election $election)
    {
        abort_if($election->academy_id !== $academy->id, 404);
        $q = (string) $r->query('q', '');
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => false, 'message' => 'กรุณาพิมพ์อย่างน้อย 2 ตัวอักษร'], 422);
        }
        $members = $this->voterRoll->eligibleMembersQuery($election)->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'))->with('user')->orderBy(User::select('name')->whereColumn('users.id', 'academy_members.user_id'))->limit(20)->get();
        $ids = $members->pluck('student_id')->filter();
        $year = $election->academic_year_id ?: DB::table('academic_years')->where('academy_id', $academy->id)->where('is_current', 1)->value('id');
        $enrolments = DB::table('classroom_students')->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')->whereIn('classroom_students.student_id', $ids)->where('classroom_students.status', 'active')->where('classroom_students.academic_year_id', $year)->get(['classroom_students.student_id', 'classrooms.grade_level', 'classrooms.name'])->keyBy('student_id');

        return response()->json(['success' => true, 'data' => $members->sortBy(fn ($m) => $m->user?->name)->map(function ($m) use ($enrolments) {
            $x = $m->student_id ? $enrolments->get($m->student_id) : null;

            return ['user_id' => $m->user_id, 'display_name' => $m->user?->name, 'voter_type' => $m->student_id ? 'student' : 'staff', 'grade_level' => $x?->grade_level, 'classroom_name' => $x?->name, 'member_code' => $m->member_code];
        })->values()]);
    }

    public function mine(Request $r, Academy $academy, Election $election)
    {
        abort_if($election->academy_id !== $academy->id, 404);
        $party = $election->parties()->where(fn ($q) => $q->where('applied_by', $r->user()->id)->orWhereHas('members', fn ($m) => $m->where('user_id', $r->user()->id)))->latest('id')->with('members.user')->first();

        return response()->json(['success' => true, 'data' => $party]);
    }

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
