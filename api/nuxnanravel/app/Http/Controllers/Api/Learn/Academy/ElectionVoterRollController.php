<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionVoter;
use App\Services\Election\ElectionVoterRollService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionVoterRollController extends Controller
{
    public function __construct(private ElectionVoterRollService $service) {}

    private function find(Academy $a, Election|int|string $e): Election
    {
        $e = $e instanceof Election ? $e : Election::whereKey($e)->firstOrFail();
        abort_if((int) $e->academy_id !== (int) $a->id, 404);

        return $e;
    }

    public function lock(Academy $a, $election, Request $r)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->lock($this->find(Academy::findOrFail($r->route('academy')), $r->route('election')), $r->user())]);
        } catch (DomainException $x) {
            return response()->json(['success' => false, 'message' => $x->getMessage()], 422);
        }
    }

    public function index(Academy $a, $election, Request $r)
    {
        $e = $this->find(Academy::findOrFail($r->route('academy')), $r->route('election'));
        $missing = $r->query('missing');
        $q = ElectionVoter::where('election_id', $e->id);
        if ($r->filled('voter_type')) {
            $q->where('voter_type', $r->voter_type);
        }
        if ($r->filled('grade_level')) {
            $q->where('grade_level', $r->grade_level);
        }
        if ($r->filled('search')) {
            $q->where(fn ($x) => $x->where('display_name', 'like', '%'.$r->search.'%')->orWhere('member_code', 'like', '%'.$r->search.'%'));
        }
        if (in_array($missing, ['member_code', 'student_card'], true)) {
            if ($missing === 'member_code') {
                $q->where(fn ($x) => $x->whereNull('member_code')->orWhere('member_code', '')->orWhere('member_code', 0));
            }
            if ($missing === 'student_card') {
                $q->where('voter_type', 'student')->whereNotExists(function ($cards) use ($e) {
                    $cards->selectRaw('1')->from('student_cards')
                        ->join('academy_members as card_members', 'card_members.student_id', '=', 'student_cards.student_id')
                        ->whereColumn('card_members.id', 'election_voters.academy_member_id')
                        ->where('student_cards.academy_id', $e->academy_id)
                        ->where('student_cards.is_active_flag', 1);
                });
            }
        }

        return response()->json(['success' => true, 'data' => $q->paginate($r->integer('per_page', 50))]);
    }

    public function stats(Academy $a, Request $r)
    {
        $e = $this->find(Academy::findOrFail($r->route('academy')), $r->route('election'));
        $q = ElectionVoter::where('election_id', $e->id);

        return response()->json(['success' => true, 'data' => ['by_voter_type' => $q->clone()->select('voter_type', DB::raw('count(*) as total'))->groupBy('voter_type')->get(), 'by_grade_level' => $q->select('grade_level', DB::raw('count(*) as total'))->groupBy('grade_level')->get()]]);
    }
}
