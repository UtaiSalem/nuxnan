<?php
namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePointCampaign;
use App\Models\Lesson;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class LessonRewardCampaignController extends Controller
{
    public function __construct(protected CoursePointAccountService $service) {}

    // GET — ดู reward ที่ตั้งไว้ (รวม stats)
    public function show(Course $course, Lesson $lesson)
    {
        $this->authorizeCourseAdmin($course);
        $campaign = CoursePointCampaign::where('lesson_id', $lesson->id)
            ->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_LESSON)
            ->whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])
            ->with(['claims'])
            ->first();

        return response()->json([
            'data' => $campaign ? [
                'id'              => $campaign->id,
                'points_per_claim' => $campaign->points_per_claim,
                'max_claims'      => $campaign->max_claims,
                'total_claimed'   => $campaign->total_claimed,
                'remaining'       => $campaign->max_claims
                    ? $campaign->max_claims - $campaign->total_claimed
                    : null,
                'status'          => $campaign->status,
                'starts_at'       => $campaign->starts_at,
                'ends_at'         => $campaign->ends_at,
            ] : null,
        ]);
    }

    // POST — สร้าง reward
    public function store(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorizeCourseAdmin($course);
        $data = $request->validate([
            'points_per_claim' => 'required|integer|min:1',
            'max_claims'       => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after_or_equal:starts_at',
        ]);

        $result = $this->service->createLessonRewardCampaign(
            $course->id, $lesson->id, $data, $request->user()->id
        );
        return response()->json($result, $result['success'] ? 201 : 422);
    }

    // DELETE — ยกเลิก reward + คืน reserved
    public function destroy(Course $course, Lesson $lesson)
    {
        $this->authorizeCourseAdmin($course);
        $campaign = CoursePointCampaign::where('lesson_id', $lesson->id)
            ->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_LESSON)
            ->whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])
            ->firstOrFail();

        $result = $this->service->cancelCampaign($campaign->id);
        return response()->json($result);
    }

    private function authorizeCourseAdmin(Course $course): void
    {
        $user = auth()->user();
        abort_unless(
            $course->user_id === $user->id || $user->hasRole('admin'),
            403
        );
    }
}
