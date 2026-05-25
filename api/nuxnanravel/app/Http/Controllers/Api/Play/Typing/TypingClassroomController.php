<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\AcademyAdmin;
use App\Models\AcademyMember;
use App\Models\TypingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TypingClassroomController extends Controller
{
    /**
     * Get typing report for a classroom (academy)
     */
    public function report(int $academyId): JsonResponse
    {
        // ตรวจสิทธิ์: ครูต้องเป็น admin ของ academy นี้
        $isTeacher = AcademyAdmin::where([
            'academy_id' => $academyId,
            'user_id'    => Auth::id(),
        ])->exists();

        if (!$isTeacher) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Teacher access required.'], 403);
        }

        $memberIds = AcademyMember::where('academy_id', $academyId)->pluck('user_id');

        $stats = TypingSession::whereIn('user_id', $memberIds)
            ->selectRaw('
                user_id,
                COUNT(*) as total_sessions,
                AVG(wpm) as avg_wpm,
                MAX(wpm) as max_wpm,
                AVG(accuracy) as avg_accuracy,
                SUM(xp_earned) as total_xp,
                MAX(created_at) as last_played
            ')
            ->groupBy('user_id')
            ->with('user:id,name,avatar,profile_photo_url')
            ->get();

        // WPM trend (7 วันย้อนหลัง)
        $trend = TypingSession::whereIn('user_id', $memberIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, AVG(wpm) as avg_wpm, COUNT(*) as sessions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // นักเรียนที่ควรฝึกเพิ่ม (avg_wpm < 20)
        $needHelp = $stats->filter(function($s) {
            return $s->avg_wpm < 20;
        })->values();

        return response()->json([
            'success'   => true,
            'data'      => [
                'students'  => $stats,
                'trend'     => $trend,
                'need_help' => $needHelp,
                'summary'   => [
                    'total_students'  => $memberIds->count(),
                    'active_students' => $stats->count(),
                    'class_avg_wpm'   => round($stats->avg('avg_wpm'), 1),
                    'class_avg_acc'   => round($stats->avg('avg_accuracy'), 1),
                ],
            ],
        ]);
    }
}
