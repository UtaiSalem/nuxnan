<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursePointWithdrawal\StoreCoursePointWithdrawalRequest;
use App\Http\Resources\CoursePointWithdrawal\CoursePointWithdrawalResource;
use App\Models\Course;
use App\Models\CoursePointWithdrawalRequest;
use App\Services\CoursePointWithdrawalService;
use Illuminate\Http\Request;

class CoursePointWithdrawalController extends Controller
{
    public function __construct(protected CoursePointWithdrawalService $service) {}

    public function index(Request $r, Course $course)
    {
        $this->authorize('viewAny', [CoursePointWithdrawalRequest::class, $course]);

        return CoursePointWithdrawalResource::collection(CoursePointWithdrawalRequest::with(['course', 'requester', 'reviewer', 'approver', 'payer'])->where('course_id', $course->id)->latest()->paginate());
    }

    public function store(StoreCoursePointWithdrawalRequest $r, Course $course)
    {
        return (new CoursePointWithdrawalResource($this->service->request($r->user(), $course, $r->integer('amount'), $r->string('purpose')->toString() ?: null, $r->header('Idempotency-Key'))))->response()->setStatusCode(201);
    }

    public function cancel(CoursePointWithdrawalRequest $withdrawal, Request $r)
    {
        $this->authorize('view', $withdrawal);

        return new CoursePointWithdrawalResource($this->service->cancel($withdrawal, $r->user()));
    }
}
