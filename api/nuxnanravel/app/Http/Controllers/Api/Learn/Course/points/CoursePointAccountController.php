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
        $account = $this->service->getAccount($course->id);

        // แอดมินรายวิชาเห็นทั้งก้อนเหมือนเดิม — shape เดิมห้ามเปลี่ยน
        // (หน้าตั้งค่ารายวิชา / LessonForm / QuizRewardForm / CampaignCreateModal
        //  อ่าน available_balance, total_earned, total_withdrawn, minimum_withdrawal อยู่)
        if ($course->isAdmin(auth()->user())) {
            return response()->json([
                'data' => $account ?? [
                    'balance' => 0, 'total_earned' => 0,
                    'total_withdrawn' => 0, 'total_distributed' => 0,
                    'minimum_withdrawal' => 24000,
                ],
            ]);
        }

        // ผู้ใช้ทั่วไปเห็นเฉพาะตัวเลข "ยอดกองทุน" ที่ UI แสดงต่อสาธารณะอยู่แล้ว
        // (CourseHero, CourseSupportWidget, CourseSupportPanel) — ตัวเลขคลังเงินไม่หลุด
        return response()->json([
            'data' => [
                'balance' => (int) ($account->balance ?? 0),
                'total_distributed' => (int) ($account->total_distributed ?? 0),
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
        // ใช้นิยามเดียวกับ Course::isAdmin() — owner / super admin / member role 4
        abort_unless(
            $course->isAdmin(auth()->user()),
            403,
            'ไม่มีสิทธิ์จัดการบัญชีแต้มรายวิชานี้'
        );
    }
}
