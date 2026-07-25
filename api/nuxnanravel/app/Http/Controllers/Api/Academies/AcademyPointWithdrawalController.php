<?php

namespace App\Http\Controllers\Api\Academies;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademyPointWithdrawal\StoreAcademyPointWithdrawalRequest;
use App\Http\Resources\AcademyPointWithdrawal\AcademyPointWithdrawalResource;
use App\Models\Academy;
use App\Models\AcademyPointWithdrawalRequest;
use App\Services\AcademyPointWithdrawalService;
use Illuminate\Http\Request;

class AcademyPointWithdrawalController extends Controller
{
    public function __construct(protected AcademyPointWithdrawalService $service) {}

    public function index(Request $r, Academy $academy)
    {
        $this->authorize('viewAny', [AcademyPointWithdrawalRequest::class, $academy]);

        return AcademyPointWithdrawalResource::collection(AcademyPointWithdrawalRequest::with(['academy', 'requester', 'reviewer', 'approver', 'payer'])->where('academy_id', $academy->id)->latest()->paginate());
    }

    public function store(StoreAcademyPointWithdrawalRequest $r, Academy $academy)
    {
        return (new AcademyPointWithdrawalResource($this->service->request($r->user(), $academy, $r->integer('amount'), $r->string('purpose')->toString() ?: null, $r->header('Idempotency-Key'))))->response()->setStatusCode(201);
    }

    public function cancel(AcademyPointWithdrawalRequest $withdrawal, Request $r)
    {
        $this->authorize('view', $withdrawal);

        return new AcademyPointWithdrawalResource($this->service->cancel($withdrawal, $r->user()));
    }
}
