<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\PublicLeaderboardUserResource;
use App\Http\Resources\PublicUserResource;
use App\Models\Course;
use App\Models\Donate;
use App\Models\Lesson;
use App\Models\Post;
use App\Models\User;
use App\Models\VisitorCounter;
use Illuminate\Support\Facades\Log;

class WelcomeController extends Controller
{
    public function index()
    {
        try {
            // Increment visitor counter
            $visitorCounterModel = VisitorCounter::first();
            if ($visitorCounterModel) {
                $visitorCounterModel->increment('visitor_counter');
                $visitorCounter = $visitorCounterModel->visitor_counter;
            } else {
                // Handle case where VisitorCounter might not exist yet
                $visitorCounter = 0;
            }

            $ceo = User::find(1);

            // route นี้เป็นสาธารณะ (ไม่ต้องล็อกอิน) — ข้อมูลผู้ใช้ต้องผ่าน resource สาธารณะเท่านั้น
            // ห้ามกลับไปใช้ UserResource ที่นี่ (มันส่ง email / เบอร์โทร / wallet)
            return response()->json([
                'usersCount' => User::count(),
                'coursesCount' => Course::count(),
                'lessonsCount' => Lesson::count(),
                'postsCount' => Post::count(),
                'visitorCounter' => $visitorCounter,

                'donates' => DonateResource::collection(Donate::with('donor')->where('status', 1)->orderBy('remaining_points', 'DESC')->latest()->paginate(8)),
                'donateRecipients' => PublicLeaderboardUserResource::collection(User::whereNotIn('id', [1])->withCardCounts()->orderBy('pp', 'DESC')->latest()->paginate(12)),

                'ceo' => $ceo ? new PublicUserResource($ceo) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Welcome endpoint failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => true,
                'message' => 'ไม่สามารถโหลดข้อมูลหน้าแรกได้',
            ], 500);
        }
    }
}
