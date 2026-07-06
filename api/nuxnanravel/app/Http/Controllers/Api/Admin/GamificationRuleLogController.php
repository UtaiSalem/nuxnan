<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GamificationRuleLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationRuleLogController extends Controller
{
    /**
     * Display a listing of gamification rule logs.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = GamificationRuleLog::with(['user', 'usageEvent']);

        // Filter by user_id
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by result (awarded, skipped, error)
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        // Filter by rule_key
        if ($request->filled('rule_key')) {
            $query->where('rule_key', $request->rule_key);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('evaluated_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('evaluated_at', '<=', $request->date_to);
        }

        $perPage = $request->input('per_page', 20);
        $logs = $query->latest('evaluated_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ],
        ]);
    }
}
