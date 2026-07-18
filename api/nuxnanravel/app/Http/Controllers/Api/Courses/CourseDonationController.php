<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseDonate\StoreCashDonationRequest;
use App\Http\Requests\CourseDonate\StorePointDonationRequest;
use App\Http\Resources\CourseDonate\CourseDonateResource;
use App\Models\Course;
use App\Models\CourseDonate;
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

    public function showForCourse(Course $course)
    {
        abort_unless(auth()->id() === $course->user_id || auth()->user()->isSuperAdmin() || auth()->user()->is_plearnd_admin, 403);

        return CourseDonateResource::collection(CourseDonate::where('course_id', $course->id)->latest()->paginate());
    }
}
