<?php

namespace App\Http\Controllers\Api\Academies;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Services\AcademyClaimService;
use DomainException;
use Illuminate\Http\Request;

class AcademyClaimController extends Controller
{
    public function __construct(protected AcademyClaimService $service) {}

    public function claimable(Request $request, Academy $academy)
    {
        abort_unless($request->user()->can('claim', $academy), 403);
        $data = $this->service->listClaimable($request->user(), $academy, $request->integer('page', 1), $request->integer('per_page', 12));

        return response()->json($data);
    }

    public function claims(Request $request, Academy $academy)
    {
        abort_unless($request->user()->can('claim', $academy), 403);

        return response()->json($this->service->listClaims($request->user(), $academy, $request->integer('page', 1), $request->integer('per_page', 15)));
    }

    public function claimFromDonation(Request $request, Academy $academy, AcademyDonate $donation)
    {
        abort_unless($request->user()->can('claim', $academy), 403);
        abort_unless((int) $donation->academy_id === (int) $academy->id, 404);
        try {
            $claim = $this->service->claimSpecific($request->user(), $academy, $donation);

            return response()->json(['ok' => true, 'claim' => $claim, 'wallet' => ['pp' => $request->user()->fresh()->pp, 'delta' => (int) $claim->amount_claimer]]);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'daily_cap_reached', 'per_donation_cap_reached' => 429,
                'insufficient_pool' => 409,
                'no_claimable_pool' => 404,
                'donation_not_claimable' => 422,
                default => 422,
            };

            return response()->json(['ok' => false, 'code' => $code], $status);
        }
    }
}
