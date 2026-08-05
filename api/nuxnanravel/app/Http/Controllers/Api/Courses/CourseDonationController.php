<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseDonate\StoreCashDonationRequest;
use App\Http\Requests\CourseDonate\StorePointDonationRequest;
use App\Http\Resources\CourseDonate\CourseDonateResource;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CourseMember;
use App\Services\CourseDonateService;
use Illuminate\Http\Request;

class CourseDonationController extends Controller
{
    public function __construct(protected CourseDonateService $service) {}

    public function storePoint(StorePointDonationRequest $r, Course $course)
    {
        return new CourseDonateResource($this->service->createPointDonation($r->user(), $course, $r->integer('points_amount'), $r->validated(), $r->header('Idempotency-Key')));
    }

    public function storeCash(StoreCashDonationRequest $r, Course $course)
    {
        return new CourseDonateResource($this->service->createCashDonation($r->user(), $course, (float) $r->input('cash_amount'), $r->validated(), $r->header('Idempotency-Key'), $r->file('slip')));
    }

    public function mine(Request $r)
    {
        return CourseDonateResource::collection(CourseDonate::where('donor_id', $r->user()->id)->latest()->paginate());
    }

    public function showForCourse(Request $request, Course $course)
    {
        $user = $request->user();
        $privileged = (int) $course->user_id === (int) $user->id || $user->isSuperAdmin() || $user->is_plearnd_admin;
        // Members need to see who funded the course before they claim from it.
        abort_unless($privileged || CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->exists(), 403);

        // Donor contact details stay with the course owner and platform admins.
        $request->attributes->set('course_donate_contact_visible', $privileged);

        $query = CourseDonate::where('course_id', $course->id);
        if (! $privileged) {
            $query->whereIn('status', [CourseDonate::STATUS_APPROVED, CourseDonate::STATUS_COMPLETED]);
        }

        return CourseDonateResource::collection($query->latest()->paginate());
    }
}
