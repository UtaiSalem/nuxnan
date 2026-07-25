<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursePointWithdrawal\ApproveRequest;
use App\Http\Requests\CoursePointWithdrawal\MarkPaidRequest;
use App\Http\Requests\CoursePointWithdrawal\RejectRequest;
use App\Http\Requests\CoursePointWithdrawal\ReviewRequest;
use App\Http\Resources\CoursePointWithdrawal\CoursePointWithdrawalResource;
use App\Models\CoursePointWithdrawalRequest;
use App\Services\CoursePointWithdrawalService;
use DomainException;
use Illuminate\Http\Request;

class CoursePointWithdrawalAdminController extends Controller
{
    public function __construct(protected CoursePointWithdrawalService $service) {}

    public function index(Request $r)
    {
        $q = CoursePointWithdrawalRequest::with(['course', 'requester', 'reviewer', 'approver', 'payer']);
        foreach (['status', 'course_id', 'requested_by'] as $field) {
            if ($r->filled($field)) {
                $q->where($field, $r->input($field));
            }
        } if ($r->filled('search')) {
            $q->where('purpose', 'like', '%'.$r->input('search').'%');
        } $sort = in_array($r->input('sort'), ['created_at', 'amount', 'status'], true) ? $r->input('sort') : 'created_at';

        return CoursePointWithdrawalResource::collection($q->orderBy($sort, $r->input('direction') === 'asc' ? 'asc' : 'desc')->paginate());
    }

    public function show(CoursePointWithdrawalRequest $withdrawal)
    {
        $this->authorize('moderate', $withdrawal);

        return new CoursePointWithdrawalResource($withdrawal->load(['course', 'requester', 'reviewer', 'approver', 'payer']));
    }

    public function review(ReviewRequest $r, CoursePointWithdrawalRequest $withdrawal)
    {
        return new CoursePointWithdrawalResource($this->service->review($withdrawal, $r->user()));
    }

    public function approve(ApproveRequest $r, CoursePointWithdrawalRequest $withdrawal)
    {
        try {
            return new CoursePointWithdrawalResource($this->service->approve($withdrawal, $r->user(), $r->input('note')));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reject(RejectRequest $r, CoursePointWithdrawalRequest $withdrawal)
    {
        return new CoursePointWithdrawalResource($this->service->reject($withdrawal, $r->user(), $r->input('reason')));
    }

    public function markPaid(MarkPaidRequest $r, CoursePointWithdrawalRequest $withdrawal)
    {
        return new CoursePointWithdrawalResource($this->service->markPaid($withdrawal, $r->user(), $r->input('payment_reference'), []));
    }
}
