<?php

namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePointTransaction;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class CoursePointAccountController extends Controller
{
    public function __construct(
        protected CoursePointAccountService $service
    ) {}

    // GET /courses/{course}/points/account
    public function show(Course $course)
    {
        $this->authorizeCourseAdmin($course);
        $account = $this->service->getAccount($course->id);

        return response()->json([
            'data' => $account ?? [
                'balance' => 0, 'total_earned' => 0,
                'total_withdrawn' => 0, 'total_distributed' => 0,
                'minimum_withdrawal' => 24000,
            ],
        ]);
    }

    // GET /courses/{course}/points/transactions
    public function transactions(Request $request, Course $course)
    {
        $this->authorizeCourseAdmin($course);
        $txs = CoursePointTransaction::where('course_id', $course->id)
            ->latest()
            ->paginate(20);

        return response()->json($txs);
    }

    // POST /courses/{course}/points/withdraw
    public function withdraw(Request $request, Course $course)
    {
        $this->authorizeCourseAdmin($course);
        $request->validate(['amount' => 'required|integer|min:1']);

        $result = $this->service->withdraw(
            courseId: $course->id,
            recipient: $request->user(),
            amount: $request->amount,
            performedBy: $request->user()->id,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function authorizeCourseAdmin(Course $course): void
    {
        $user = auth()->user();
        // owner หรือ admin
        abort_unless(
            $course->user_id === $user->id || $user->hasRole('admin'),
            403,
            'ไม่มีสิทธิ์จัดการบัญชีแต้มรายวิชานี้'
        );
    }
}
