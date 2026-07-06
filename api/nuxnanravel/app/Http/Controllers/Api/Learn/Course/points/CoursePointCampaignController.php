<?php

namespace App\Http\Controllers\Api\Learn\Course\points;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePointCampaign;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;

class CoursePointCampaignController extends Controller
{
    public function __construct(
        protected CoursePointAccountService $service
    ) {}

    // GET /courses/{course}/points/campaigns
    public function index(Course $course)
    {
        $campaigns = CoursePointCampaign::where('course_id', $course->id)
            ->latest()->get();

        return response()->json(['data' => $campaigns]);
    }

    // POST /courses/{course}/points/campaigns
    public function store(Request $request, Course $course)
    {
        $this->authorizeCourseAdmin($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_per_claim' => 'required|integer|min:1',
            'max_claims' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $result = $this->service->createCampaign($course->id, $data, $request->user()->id);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    // POST /courses/{course}/points/campaigns/{campaign}/claim
    public function claim(Course $course, CoursePointCampaign $campaign)
    {
        // ตรวจว่า campaign เป็นของ course นี้
        abort_if($campaign->course_id !== $course->id, 404);

        $result = $this->service->claimCampaign($campaign->id, auth()->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function pause(Course $course, CoursePointCampaign $campaign)
    {
        $this->authorizeCourseAdmin($course);
        $campaign->update(['status' => CoursePointCampaign::STATUS_PAUSED]);

        return response()->json(['success' => true]);
    }

    public function end(Course $course, CoursePointCampaign $campaign)
    {
        $this->authorizeCourseAdmin($course);
        $campaign->update(['status' => CoursePointCampaign::STATUS_ENDED]);

        return response()->json(['success' => true]);
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
