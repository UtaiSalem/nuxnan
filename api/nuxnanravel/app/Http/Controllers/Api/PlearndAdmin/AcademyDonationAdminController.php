<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademyDonate\AcademyDonateResource;
use App\Models\AcademyDonate;
use App\Services\AcademyDonateService;
use Illuminate\Http\Request;

class AcademyDonationAdminController extends Controller
{
    public function __construct(protected AcademyDonateService $service) {}

    public function index(Request $r)
    {
        return AcademyDonateResource::collection(AcademyDonate::with(['academy', 'donor'])->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->donation_type, fn ($q, $v) => $q->where('donation_type', $v))->latest()->paginate());
    }

    public function show(AcademyDonate $donation)
    {
        return new AcademyDonateResource($donation->load(['academy', 'donor']));
    }

    public function approve(AcademyDonate $donation, Request $r)
    {
        $this->authorize('moderate', $donation);
        $r->validate(['note' => 'nullable|string|max:1000']);
        abort_if($r->user()->id === $donation->academy->user_id, 403);

        return new AcademyDonateResource($this->service->approve($donation, $r->user(), $r->input('note')));
    }

    public function reject(AcademyDonate $donation, Request $r)
    {
        $this->authorize('moderate', $donation);
        $r->validate(['reason' => 'required|string|max:1000']);

        return new AcademyDonateResource($this->service->reject($donation, $r->user(), $r->input('reason')));
    }
}
