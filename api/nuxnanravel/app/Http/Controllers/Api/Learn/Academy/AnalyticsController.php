<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AnalyticsSnapshot;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\ComparativeAnalytics;
use App\Models\TrendAnalysis;
use App\Models\AnalyticsAlertRule;
use App\Models\AnalyticsAlertHistory;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    // ==================== ANALYTICS SNAPSHOTS ====================

    /**
     * Get analytics overview/summary
     */
    public function overview(Request $request, Academy $academy): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        
        $snapshots = AnalyticsSnapshot::where('academy_id', $academy->id)
            ->forDate($date)
            ->get()
            ->keyBy('category');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'snapshots' => $snapshots,
            ],
        ]);
    }

    /**
     * Get snapshots by category
     */
    public function getSnapshots(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(AnalyticsSnapshot::CATEGORIES)],
            'type' => ['nullable', Rule::in(AnalyticsSnapshot::TYPES)],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = AnalyticsSnapshot::where('academy_id', $academy->id)
            ->byCategory($validated['category']);

        if (!empty($validated['type'])) {
            $query->byType($validated['type']);
        }

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->betweenDates($validated['start_date'], $validated['end_date']);
        }

        $snapshots = $query->orderByDesc('snapshot_date')
            ->paginate($request->get('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    }

    // ==================== KPI DEFINITIONS ====================

    /**
     * List KPI definitions
     */
    public function listKpis(Request $request, Academy $academy): JsonResponse
    {
        $query = KpiDefinition::where('academy_id', $academy->id);

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $kpis = $query->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Create KPI definition
     */
    public function createKpi(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:kpi_definitions,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(KpiDefinition::CATEGORIES)],
            'metric_type' => ['required', Rule::in(KpiDefinition::METRIC_TYPES)],
            'calculation' => 'nullable|array',
            'target_value' => 'nullable|numeric',
            'warning_threshold' => 'nullable|numeric',
            'critical_threshold' => 'nullable|numeric',
            'comparison_direction' => ['required', Rule::in([
                KpiDefinition::DIRECTION_HIGHER_IS_BETTER,
                KpiDefinition::DIRECTION_LOWER_IS_BETTER,
            ])],
        ]);

        $validated['academy_id'] = $academy->id;

        $kpi = KpiDefinition::create($validated);

        $this->auditLog->log(
            'kpi_created',
            KpiDefinition::class,
            $kpi->id,
            ['name' => $kpi->name, 'code' => $kpi->code]
        );

        return response()->json([
            'success' => true,
            'message' => 'KPI created successfully',
            'data' => $kpi,
        ], 201);
    }

    /**
     * Show KPI with latest values
     */
    public function showKpi(Academy $academy, KpiDefinition $kpi): JsonResponse
    {
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $latestValues = $kpi->values()
            ->orderByDesc('period_date')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => $kpi,
                'latest_values' => $latestValues,
            ],
        ]);
    }

    /**
     * Update KPI definition
     */
    public function updateKpi(Request $request, Academy $academy, KpiDefinition $kpi): JsonResponse
    {
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => ['sometimes', Rule::in(KpiDefinition::CATEGORIES)],
            'metric_type' => ['sometimes', Rule::in(KpiDefinition::METRIC_TYPES)],
            'calculation' => 'nullable|array',
            'target_value' => 'nullable|numeric',
            'warning_threshold' => 'nullable|numeric',
            'critical_threshold' => 'nullable|numeric',
            'comparison_direction' => ['sometimes', Rule::in([
                KpiDefinition::DIRECTION_HIGHER_IS_BETTER,
                KpiDefinition::DIRECTION_LOWER_IS_BETTER,
            ])],
        ]);

        $kpi->update($validated);

        $this->auditLog->log(
            'kpi_updated',
            KpiDefinition::class,
            $kpi->id,
            ['changes' => array_keys($validated)]
        );

        return response()->json([
            'success' => true,
            'message' => 'KPI updated successfully',
            'data' => $kpi->fresh(),
        ]);
    }

    /**
     * Delete KPI
     */
    public function deleteKpi(Academy $academy, KpiDefinition $kpi): JsonResponse
    {
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $kpiId = $kpi->id;
        $kpiName = $kpi->name;
        
        $kpi->delete();

        $this->auditLog->log(
            'kpi_deleted',
            KpiDefinition::class,
            $kpiId,
            ['name' => $kpiName]
        );

        return response()->json([
            'success' => true,
            'message' => 'KPI deleted successfully',
        ]);
    }

    /**
     * Get KPI values history
     */
    public function getKpiValues(Request $request, Academy $academy, KpiDefinition $kpi): JsonResponse
    {
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'period_type' => ['nullable', Rule::in(KpiValue::PERIOD_TYPES)],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = $kpi->values();

        if (!empty($validated['period_type'])) {
            $query->byPeriodType($validated['period_type']);
        }

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->betweenDates($validated['start_date'], $validated['end_date']);
        }

        $values = $query->orderByDesc('period_date')
            ->paginate($request->get('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => $values,
        ]);
    }

    /**
     * Record KPI value
     */
    public function recordKpiValue(Request $request, Academy $academy, KpiDefinition $kpi): JsonResponse
    {
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'period_date' => 'required|date',
            'period_type' => ['required', Rule::in(KpiValue::PERIOD_TYPES)],
            'value' => 'required|numeric',
            'previous_value' => 'nullable|numeric',
            'breakdown' => 'nullable|array',
        ]);

        $kpiValue = KpiValue::recordValue(
            $kpi->id,
            $validated['period_date'],
            $validated['period_type'],
            $validated['value'],
            $validated['previous_value'] ?? null,
            $validated['breakdown'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'KPI value recorded',
            'data' => $kpiValue,
        ], 201);
    }

    // ==================== COMPARATIVE ANALYTICS ====================

    /**
     * Get comparative analytics
     */
    public function getComparisons(Request $request, Academy $academy): JsonResponse
    {
        $query = ComparativeAnalytics::where('academy_id', $academy->id);

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $comparisons = $query->recent()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $comparisons,
        ]);
    }

    /**
     * Generate comparison
     */
    public function generateComparison(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'comparison_type' => ['required', Rule::in(ComparativeAnalytics::TYPES)],
            'category' => ['required', Rule::in(ComparativeAnalytics::CATEGORIES)],
            'period_1' => 'required|array',
            'period_1.start' => 'required|date',
            'period_1.end' => 'required|date|after_or_equal:period_1.start',
            'period_1.label' => 'nullable|string',
            'period_2' => 'required|array',
            'period_2.start' => 'required|date',
            'period_2.end' => 'required|date|after_or_equal:period_2.start',
            'period_2.label' => 'nullable|string',
        ]);

        // TODO: Implement actual comparison calculation based on category
        $metrics = []; // This should be calculated

        $comparison = ComparativeAnalytics::generate(
            $academy->id,
            $validated['comparison_type'],
            $validated['category'],
            $validated['period_1'],
            $validated['period_2'],
            $metrics
        );

        $this->auditLog->log(
            'comparative_analytics_generated',
            ComparativeAnalytics::class,
            $comparison->id,
            ['type' => $comparison->comparison_type, 'category' => $comparison->category]
        );

        return response()->json([
            'success' => true,
            'message' => 'Comparison generated',
            'data' => $comparison,
        ], 201);
    }

    // ==================== TREND ANALYSIS ====================

    /**
     * Get trend analyses
     */
    public function getTrends(Request $request, Academy $academy): JsonResponse
    {
        $query = TrendAnalysis::where('academy_id', $academy->id);

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('metric')) {
            $query->byMetric($request->metric);
        }

        $trends = $query->recent()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Generate trend analysis
     */
    public function generateTrend(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'metric_name' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'granularity' => ['required', Rule::in(TrendAnalysis::GRANULARITIES)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // TODO: Implement actual trend calculation
        $dataPoints = []; // Should be fetched based on metric and date range
        $trendInfo = [
            'direction' => 'stable',
            'slope' => 0,
            'r_squared' => 0,
        ];
        $forecast = [];

        $trend = TrendAnalysis::generate(
            $academy->id,
            $validated['metric_name'],
            $validated['category'],
            $validated['granularity'],
            $validated['start_date'],
            $validated['end_date'],
            $dataPoints,
            $trendInfo,
            $forecast
        );

        $this->auditLog->log(
            'trend_analysis_generated',
            TrendAnalysis::class,
            $trend->id,
            ['metric' => $trend->metric_name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Trend analysis generated',
            'data' => $trend,
        ], 201);
    }

    // ==================== ALERT RULES ====================

    /**
     * List alert rules
     */
    public function listAlertRules(Request $request, Academy $academy): JsonResponse
    {
        $query = AnalyticsAlertRule::where('academy_id', $academy->id)
            ->with('kpi');

        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $rules = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Create alert rule
     */
    public function createAlertRule(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'kpi_id' => 'required|exists:kpi_definitions,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => ['required', Rule::in(AnalyticsAlertRule::CONDITION_TYPES)],
            'operator' => ['required', Rule::in(AnalyticsAlertRule::OPERATORS)],
            'threshold_value' => 'required|numeric',
            'alert_level' => ['required', Rule::in(AnalyticsAlertRule::LEVELS)],
            'notification_channels' => 'nullable|array',
            'recipients' => 'nullable|array',
        ]);

        $kpi = KpiDefinition::findOrFail($validated['kpi_id']);
        if ($kpi->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'KPI not found in this academy',
            ], 404);
        }

        $validated['academy_id'] = $academy->id;

        $rule = AnalyticsAlertRule::create($validated);

        $this->auditLog->log(
            'alert_rule_created',
            AnalyticsAlertRule::class,
            $rule->id,
            ['name' => $rule->name, 'kpi' => $kpi->name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert rule created successfully',
            'data' => $rule->load('kpi'),
        ], 201);
    }

    /**
     * Update alert rule
     */
    public function updateAlertRule(Request $request, Academy $academy, AnalyticsAlertRule $rule): JsonResponse
    {
        if ($rule->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alert rule not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => ['sometimes', Rule::in(AnalyticsAlertRule::CONDITION_TYPES)],
            'operator' => ['sometimes', Rule::in(AnalyticsAlertRule::OPERATORS)],
            'threshold_value' => 'sometimes|numeric',
            'alert_level' => ['sometimes', Rule::in(AnalyticsAlertRule::LEVELS)],
            'notification_channels' => 'nullable|array',
            'recipients' => 'nullable|array',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Alert rule updated',
            'data' => $rule->fresh()->load('kpi'),
        ]);
    }

    /**
     * Delete alert rule
     */
    public function deleteAlertRule(Academy $academy, AnalyticsAlertRule $rule): JsonResponse
    {
        if ($rule->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alert rule not found in this academy',
            ], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert rule deleted',
        ]);
    }

    /**
     * Toggle alert rule status
     */
    public function toggleAlertRuleStatus(Academy $academy, AnalyticsAlertRule $rule): JsonResponse
    {
        if ($rule->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alert rule not found in this academy',
            ], 404);
        }

        if ($rule->is_active) {
            $rule->deactivate();
        } else {
            $rule->activate();
        }

        return response()->json([
            'success' => true,
            'message' => $rule->is_active ? 'Alert rule activated' : 'Alert rule deactivated',
            'data' => $rule->fresh(),
        ]);
    }

    // ==================== ALERT HISTORY ====================

    /**
     * Get alert history
     */
    public function getAlertHistory(Request $request, Academy $academy): JsonResponse
    {
        $query = AnalyticsAlertHistory::whereHas('alertRule', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->with(['alertRule', 'acknowledgedByUser']);

        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        if ($request->boolean('unacknowledged_only')) {
            $query->unacknowledged();
        }

        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        $history = $query->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get unacknowledged alerts count
     */
    public function getUnacknowledgedCount(Academy $academy): JsonResponse
    {
        $counts = AnalyticsAlertHistory::whereHas('alertRule', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })
            ->unacknowledged()
            ->selectRaw('alert_level, count(*) as count')
            ->groupBy('alert_level')
            ->pluck('count', 'alert_level');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $counts->sum(),
                'critical' => $counts->get('critical', 0),
                'warning' => $counts->get('warning', 0),
                'info' => $counts->get('info', 0),
            ],
        ]);
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(Academy $academy, AnalyticsAlertHistory $alert): JsonResponse
    {
        if ($alert->alertRule->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found in this academy',
            ], 404);
        }

        $alert->acknowledge(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
            'data' => $alert->fresh()->load('acknowledgedByUser'),
        ]);
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledge(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'integer|exists:analytics_alert_history,id',
        ]);

        $updated = 0;
        foreach ($validated['alert_ids'] as $alertId) {
            $alert = AnalyticsAlertHistory::find($alertId);
            if ($alert && $alert->alertRule->academy_id === $academy->id && !$alert->is_acknowledged) {
                $alert->acknowledge(Auth::id());
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} alerts acknowledged",
            'data' => ['acknowledged_count' => $updated],
        ]);
    }
}
