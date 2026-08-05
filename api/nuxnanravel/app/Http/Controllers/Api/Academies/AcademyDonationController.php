<?php

namespace App\Http\Controllers\Api\Academies;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademyDonate\StoreCashDonationRequest;
use App\Http\Requests\AcademyDonate\StorePointDonationRequest;
use App\Http\Resources\AcademyDonate\AcademyDonateResource;
use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Services\AcademyDonateService;
use Illuminate\Http\Request;

class AcademyDonationController extends Controller
{
    public function __construct(protected AcademyDonateService $service) {}

    public function storePoint(StorePointDonationRequest $r, Academy $academy)
    {
        return (new AcademyDonateResource($this->service->createPointDonation($r->user(), $academy, $r->integer('points_amount'), $r->validated(), $r->header('Idempotency-Key'))))->response()->setStatusCode(201);
    }

    public function storeCash(StoreCashDonationRequest $r, Academy $academy)
    {
        return (new AcademyDonateResource($this->service->createCashDonation($r->user(), $academy, (float) $r->input('cash_amount'), $r->validated(), $r->header('Idempotency-Key'), $r->file('slip'))))->response()->setStatusCode(201);
    }

    public function mine(Request $r)
    {
        return AcademyDonateResource::collection(AcademyDonate::with(['academy', 'donor'])->where('donor_id', $r->user()->id)->latest()->paginate());
    }

    public function showForAcademy(Request $request, Academy $academy)
    {
        $user = $request->user();
        $privileged = (int) $academy->user_id === (int) $user->id || $user->isSuperAdmin() || $user->is_plearnd_admin;
        // Members need to see who funded the school before they claim from it.
        abort_unless($privileged || $user->can('claim', $academy), 403);

        // Donor contact details stay with the academy owner and platform admins.
        $request->attributes->set('academy_donate_contact_visible', $privileged);

        $query = AcademyDonate::with(['academy', 'donor'])->where('academy_id', $academy->id);
        if (! $privileged) {
            $query->whereIn('status', [AcademyDonate::STATUS_APPROVED, AcademyDonate::STATUS_COMPLETED]);
        }

        return AcademyDonateResource::collection($query->latest()->paginate());
    }
}
