<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Services\CourseClaimService;
use DomainException;
use Illuminate\Http\Request;

class CourseClaimController extends Controller
{
    public function __construct(protected CourseClaimService $service) {}

    public function claimable(Request $request, Course $course)
    {
        try {
            $data = $this->service->listClaimable($request->user(), $course, $request->integer('page', 1), $request->integer('per_page', 12));

            return response()->json($data);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'not_a_course_member' => 403,
                default => 422,
            };

            return response()->json(['ok' => false, 'code' => $code], $status);
        }
    }

    public function claims(Request $request, Course $course)
    {
        try {
            $data = $this->service->listClaims($request->user(), $course, $request->integer('page', 1), $request->integer('per_page', 15));

            return response()->json($data);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'not_a_course_member' => 403,
                default => 422,
            };

            return response()->json(['ok' => false, 'code' => $code], $status);
        }
    }

    public function claimFromDonation(Request $request, Course $course, CourseDonate $donation)
    {
        abort_unless((int) $donation->course_id === (int) $course->id, 404);
        try {
            $claim = $this->service->claimSpecific($request->user(), $course, $donation);

            return response()->json(['ok' => true, 'claim' => $claim, 'wallet' => ['pp' => $request->user()->fresh()->pp, 'delta' => (int) $claim->amount_claimer]]);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'daily_cap_reached', 'per_donation_cap_reached' => 429,
                'insufficient_pool' => 409,
                'no_claimable_pool' => 404,
                'not_a_course_member' => 403,
                'donation_not_claimable' => 422,
                default => 422,
            };

            return response()->json(['ok' => false, 'code' => $code], $status);
        }
    }
}
