<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademyPointWithdrawal\ApproveRequest;
use App\Http\Requests\AcademyPointWithdrawal\MarkPaidRequest;
use App\Http\Requests\AcademyPointWithdrawal\RejectRequest;
use App\Http\Requests\AcademyPointWithdrawal\ReviewRequest;
use App\Http\Resources\AcademyPointWithdrawal\AcademyPointWithdrawalResource;
use App\Models\AcademyPointWithdrawalRequest;
use App\Services\AcademyPointWithdrawalService;
use DomainException;
use Illuminate\Http\Request;

class AcademyPointWithdrawalAdminController extends Controller
{
    public function __construct(protected AcademyPointWithdrawalService $service) {}

    public function index(Request $r)
    {
        $q = AcademyPointWithdrawalRequest::with(['academy', 'requester', 'reviewer', 'approver', 'payer']);
        foreach (['status', 'academy_id', 'requested_by'] as $field) {
            if ($r->filled($field)) {
                $q->where($field, $r->input($field));
            }
        } if ($r->filled('search')) {
            $q->where('purpose', 'like', '%'.$r->input('search').'%');
        } $sort = in_array($r->input('sort'), ['created_at', 'amount', 'status'], true) ? $r->input('sort') : 'created_at';

        return AcademyPointWithdrawalResource::collection($q->orderBy($sort, $r->input('direction') === 'asc' ? 'asc' : 'desc')->paginate());
    }

    public function show(AcademyPointWithdrawalRequest $withdrawal)
    {
        $this->authorize('moderate', $withdrawal);

        return new AcademyPointWithdrawalResource($withdrawal->load(['academy', 'requester', 'reviewer', 'approver', 'payer']));
    }

    public function review(ReviewRequest $r, AcademyPointWithdrawalRequest $withdrawal)
    {
        return new AcademyPointWithdrawalResource($this->service->review($withdrawal, $r->user()));
    }

    public function approve(ApproveRequest $r, AcademyPointWithdrawalRequest $withdrawal)
    {
        try {
            return new AcademyPointWithdrawalResource($this->service->approve($withdrawal, $r->user(), $r->input('note')));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reject(RejectRequest $r, AcademyPointWithdrawalRequest $withdrawal)
    {
        return new AcademyPointWithdrawalResource($this->service->reject($withdrawal, $r->user(), $r->input('reason')));
    }

    public function markPaid(MarkPaidRequest $r, AcademyPointWithdrawalRequest $withdrawal)
    {
        return new AcademyPointWithdrawalResource($this->service->markPaid($withdrawal, $r->user(), $r->input('payment_reference')));
    }
}
