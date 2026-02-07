<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,first_name,last_name,avatar');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter by entity type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%' . $request->entity_type . '%');
        }

        // Filter by entity ID
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search in metadata or values
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('JSON_SEARCH(old_values, "one", ?) IS NOT NULL', ["%{$search}%"])
                    ->orWhereRaw('JSON_SEARCH(new_values, "one", ?) IS NOT NULL', ["%{$search}%"])
                    ->orWhereRaw('JSON_SEARCH(metadata, "one", ?) IS NOT NULL', ["%{$search}%"]);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user:id,first_name,last_name,avatar');

        return response()->json([
            'success' => true,
            'data' => [
                ...$auditLog->toArray(),
                'description' => $auditLog->description,
                'changed_fields' => $auditLog->changed_fields,
            ]
        ]);
    }

    /**
     * Get audit logs for a specific entity.
     */
    public function getEntityLogs(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        $logs = AuditLog::with('user:id,first_name,last_name,avatar')
            ->where('entity_type', 'App\\Models\\' . $request->entity_type)
            ->where('entity_id', $request->entity_id)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    ...$log->toArray(),
                    'description' => $log->description,
                ];
            })
        ]);
    }

    /**
     * Get audit logs for the current user.
     */
    public function myLogs(Request $request)
    {
        $logs = AuditLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    ...$log->toArray(),
                    'description' => $log->description,
                ];
            })
        ]);
    }

    /**
     * Get available actions for filtering.
     */
    public function getActions()
    {
        $actions = AuditLog::distinct('action')
            ->pluck('action')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $actions
        ]);
    }

    /**
     * Get available modules for filtering.
     */
    public function getModules()
    {
        $modules = AuditLog::distinct('module')
            ->whereNotNull('module')
            ->pluck('module')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    /**
     * Get audit logs summary/statistics.
     */
    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $summaryByAction = $this->auditLogService->getSummaryByAction($startDate, $endDate);
        $summaryByModule = $this->auditLogService->getSummaryByModule($startDate, $endDate);

        // Get daily activity
        $dailyActivity = AuditLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get most active users
        $mostActiveUsers = AuditLog::selectRaw('user_id, COUNT(*) as action_count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('action_count', 'desc')
            ->limit(10)
            ->with('user:id,first_name,last_name,avatar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_action' => $summaryByAction,
                'by_module' => $summaryByModule,
                'daily_activity' => $dailyActivity,
                'most_active_users' => $mostActiveUsers,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ]
        ]);
    }

    /**
     * Export audit logs.
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user:id,first_name,last_name');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit(10000) // Safety limit
            ->get();

        // Log the export action
        $this->auditLogService->logExport('audit_logs', $request->all());

        return response()->json([
            'success' => true,
            'data' => $logs,
            'meta' => [
                'total' => $logs->count(),
                'exported_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Clean old audit logs (admin only).
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'retention_days' => 'required|integer|min:30|max:3650',
        ]);

        $deleted = $this->auditLogService->cleanOldLogs($request->retention_days);

        return response()->json([
            'success' => true,
            'message' => "ลบ audit logs ที่เก่ากว่า {$request->retention_days} วัน จำนวน {$deleted} รายการ",
            'data' => [
                'deleted_count' => $deleted,
                'retention_days' => $request->retention_days,
            ]
        ]);
    }
}
