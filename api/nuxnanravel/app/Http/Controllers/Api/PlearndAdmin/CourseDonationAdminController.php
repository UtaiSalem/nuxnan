<?php

namespace App\Http\Controllers\Api\PlearndAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseDonate\CourseDonateResource;
use App\Models\CourseDonate;
use App\Services\CourseDonateService;
use Illuminate\Http\Request;

class CourseDonationAdminController extends Controller
{
    public function __construct(protected CourseDonateService $service) {}

    public function index(Request $r)
    {
        return CourseDonateResource::collection(CourseDonate::where('status', CourseDonate::STATUS_PENDING)->latest()->paginate());
    }

    public function approve(CourseDonate $donation, Request $r)
    {
        $this->authorize('moderate', $donation);

        return new CourseDonateResource($this->service->approve($donation, $r->user(), $r->input('note')));
    }

    public function reject(CourseDonate $donation, Request $r)
    {
        $this->authorize('moderate', $donation);
        $r->validate(['reason' => 'required|string|max:1000']);

        return new CourseDonateResource($this->service->reject($donation, $r->user(), $r->input('reason')));
    }
}
