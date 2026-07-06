<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\XpEvent;
use App\Services\Gamification\ClassroomPointsService;
use App\Services\Gamification\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function __construct(
        protected XpService $xpService,
        protected ClassroomPointsService $classroomPointsService
    ) {}

    public function summary(Academy $academy, Request $request): JsonResponse
    {
        $cycleType = $request->input('cycle', 'all_time');
        $summary = $this->xpService->summary($academy, $cycleType);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    public function leaderboard(Academy $academy, Request $request): JsonResponse
    {
        $cycleType = $request->input('cycle', config('gamification.leaderboard_cycle', 'month'));
        $limit = (int) $request->input('limit', 3);
        $rows = $this->classroomPointsService->leaderboard($academy->id, $cycleType, $limit);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function recentEvents(Academy $academy, Request $request): JsonResponse
    {
        $events = XpEvent::where('academy_id', $academy->id)
            ->with(['user:id,name', 'classroomGroup:id,name'])
            ->latest('occurred_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
