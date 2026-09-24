<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportSchedule;
use App\Models\SavedReport;
use App\Services\AuditLogService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    // ==================== REPORT DEFINITIONS ====================

    /**
     * List report definitions
     */
    public function listDefinitions(Request $request, Academy $academy): JsonResponse
    {
        $query = ReportDefinition::where('academy_id', $academy->id);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $definitions = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $definitions,
        ]);
    }

    /**
     * Create report definition
     */
    public function createDefinition(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(ReportDefinition::CATEGORIES)],
            'report_type' => ['required', Rule::in(ReportDefinition::TYPES)],
            'data_source' => 'required|string|max:100',
            'columns' => 'required|array',
            'filters' => 'nullable|array',
            'default_params' => 'nullable|array',
        ]);

        $validated['academy_id'] = $academy->id;
        $validated['created_by'] = Auth::id();

        $definition = ReportDefinition::create($validated);

        $this->auditLog->log(
            'report_definition_created',
            ReportDefinition::class,
            $definition->id,
            ['name' => $definition->name, 'category' => $definition->category]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report definition created successfully',
            'data' => $definition,
        ], 201);
    }

    /**
     * Show report definition
     */
    public function showDefinition(Academy $academy, ReportDefinition $definition): JsonResponse
    {
        if ($definition->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $definition,
        ]);
    }

    /**
     * Update report definition
     */
    public function updateDefinition(Request $request, Academy $academy, ReportDefinition $definition): JsonResponse
    {
        if ($definition->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => ['sometimes', Rule::in(ReportDefinition::CATEGORIES)],
            'report_type' => ['sometimes', Rule::in(ReportDefinition::TYPES)],
            'data_source' => 'sometimes|string|max:100',
            'columns' => 'sometimes|array',
            'filters' => 'nullable|array',
            'default_params' => 'nullable|array',
        ]);

        $definition->update($validated);

        $this->auditLog->log(
            'report_definition_updated',
            ReportDefinition::class,
            $definition->id,
            ['changes' => array_keys($validated)]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report definition updated successfully',
            'data' => $definition->fresh(),
        ]);
    }

    /**
     * Delete report definition
     */
    public function deleteDefinition(Academy $academy, ReportDefinition $definition): JsonResponse
    {
        if ($definition->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        $definitionId = $definition->id;
        $definitionName = $definition->name;

        $definition->delete();

        $this->auditLog->log(
            'report_definition_deleted',
            ReportDefinition::class,
            $definitionId,
            ['name' => $definitionName]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report definition deleted successfully',
        ]);
    }

    /**
     * Activate/Deactivate report definition
     */
    public function toggleDefinitionStatus(Academy $academy, ReportDefinition $definition): JsonResponse
    {
        if ($definition->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        if ($definition->is_active) {
            $definition->deactivate();
        } else {
            $definition->activate();
        }

        return response()->json([
            'success' => true,
            'message' => $definition->is_active ? 'Report activated' : 'Report deactivated',
            'data' => $definition->fresh(),
        ]);
    }

    /**
     * Duplicate report definition
     */
    public function duplicateDefinition(Academy $academy, ReportDefinition $definition): JsonResponse
    {
        if ($definition->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        $newDefinition = $definition->duplicate();

        $this->auditLog->log(
            'report_definition_duplicated',
            ReportDefinition::class,
            $newDefinition->id,
            ['source_id' => $definition->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report definition duplicated successfully',
            'data' => $newDefinition,
        ], 201);
    }

    // ==================== SAVED REPORTS ====================

    /**
     * List saved reports for current user
     */
    public function listSavedReports(Request $request, Academy $academy): JsonResponse
    {
        $query = SavedReport::where('academy_id', $academy->id)
            ->where('user_id', Auth::id())
            ->with('definition');

        if ($request->boolean('favorites_only')) {
            $query->favorites();
        }

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Generate and save a report
     */
    public function generateReport(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'definition_id' => 'required|exists:report_definitions,id',
            'name' => 'required|string|max:255',
            'parameters' => 'nullable|array',
        ]);

        $definition = ReportDefinition::findOrFail($validated['definition_id']);

        if ($definition->academy_id !== $academy->id && $definition->academy_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Report definition not found in this academy',
            ], 404);
        }

        // Simple query-backed generation logic
        $data = [];
        $params = $validated['parameters'] ?? [];
        $sourceType = is_array($definition->data_source) ? ($definition->data_source['type'] ?? '') : $definition->data_source;

        switch ($sourceType) {
            case 'school_attendances':
                $data = DB::table('school_attendance_records')
                    ->join('school_attendances', 'school_attendance_records.attendance_id', '=', 'school_attendances.id')
                    ->select(
                        'school_attendances.date',
                        'school_attendances.title',
                        DB::raw("SUM(CASE WHEN school_attendance_records.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                        DB::raw("SUM(CASE WHEN school_attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                        DB::raw("SUM(CASE WHEN school_attendance_records.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                        DB::raw('COUNT(*) as total_count')
                    )
                    ->where('school_attendance_records.academy_id', $academy->id)
                    ->groupBy('school_attendances.date', 'school_attendances.title')
                    ->orderByDesc('school_attendances.date')
                    ->get();
                break;

            case 'tuition_fees':
                // tuition_fees ผูกด้วย student_id (→ students.id) ไม่มีคอลัมน์ user_id · ยอดค้างคือ balance_amount
                $data = DB::table('tuition_fees')
                    ->join('students', 'tuition_fees.student_id', '=', 'students.id')
                    ->join('users', 'students.user_id', '=', 'users.id')
                    ->leftJoin('classrooms', 'tuition_fees.classroom_id', '=', 'classrooms.id')
                    ->select('users.name as student_name', 'classrooms.name as classroom_name',
                        'tuition_fees.total_amount', 'tuition_fees.paid_amount',
                        'tuition_fees.balance_amount as remaining_amount', 'tuition_fees.due_date')
                    ->where('tuition_fees.academy_id', $academy->id)
                    ->get();
                break;

            case 'at_risk_students':
                $atRiskResponse = app(AnalyticsController::class)->getAtRiskStudents($request, $academy);
                $data = $atRiskResponse->getData()->data;
                break;
        }

        $savedReport = SavedReport::create([
            'academy_id' => $academy->id,
            'definition_id' => $definition->id,
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'parameters' => $params,
            'cached_data' => $data,
            'generated_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->auditLog->log(
            'report_generated',
            SavedReport::class,
            $savedReport->id,
            ['definition' => $definition->name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $savedReport->load('definition'),
        ], 201);
    }

    /**
     * Show saved report
     */
    public function showSavedReport(Academy $academy, SavedReport $report): JsonResponse
    {
        if ($report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $report->load('definition'),
        ]);
    }

    /**
     * Delete saved report
     */
    public function deleteSavedReport(Academy $academy, SavedReport $report): JsonResponse
    {
        if ($report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(Academy $academy, SavedReport $report): JsonResponse
    {
        if ($report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        $report->toggleFavorite();

        return response()->json([
            'success' => true,
            'message' => $report->is_favorite ? 'Added to favorites' : 'Removed from favorites',
            'data' => ['is_favorite' => $report->is_favorite],
        ]);
    }

    /**
     * Refresh saved report data
     */
    public function refreshReport(Academy $academy, SavedReport $report): JsonResponse
    {
        if ($report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        // TODO: Implement actual refresh logic
        $report->updateCachedData([]);

        return response()->json([
            'success' => true,
            'message' => 'Report refreshed successfully',
            'data' => $report->fresh()->load('definition'),
        ]);
    }

    // ==================== REPORT SCHEDULES ====================

    /**
     * List report schedules
     */
    public function listSchedules(Request $request, Academy $academy): JsonResponse
    {
        $query = ReportSchedule::whereHas('report', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })->with(['report']);

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $schedules = $query->orderBy('next_run_at')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    /**
     * Create report schedule
     */
    public function createSchedule(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'saved_report_id' => 'required|exists:saved_reports,id',
            'frequency' => ['required', Rule::in(ReportSchedule::FREQUENCIES)],
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_of_day' => 'required|date_format:H:i',
            'export_format' => ['required', Rule::in(ReportSchedule::FORMATS)],
            'recipients' => 'required|array',
            'recipients.*' => 'email',
        ]);

        $savedReport = SavedReport::findOrFail($validated['saved_report_id']);

        if ($savedReport->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        // คอลัมน์จริงคือ report_id/scheduled_time (ไม่ใช่ saved_report_id/time_of_day) + ต้องมี academy_id/user_id
        $schedule = ReportSchedule::create([
            'academy_id' => $academy->id,
            'report_id' => $savedReport->id,
            'user_id' => Auth::id(),
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'scheduled_time' => $validated['time_of_day'],
            'export_format' => $validated['export_format'],
            'recipients' => $validated['recipients'],
        ]);
        $schedule->calculateNextRun();

        $this->auditLog->log(
            'report_schedule_created',
            ReportSchedule::class,
            $schedule->id,
            ['frequency' => $schedule->frequency, 'report' => $savedReport->name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load('report'),
        ], 201);
    }

    /**
     * Update schedule
     */
    public function updateSchedule(Request $request, Academy $academy, ReportSchedule $schedule): JsonResponse
    {
        if ($schedule->report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'frequency' => ['sometimes', Rule::in(ReportSchedule::FREQUENCIES)],
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_of_day' => 'sometimes|date_format:H:i',
            'export_format' => ['sometimes', Rule::in(ReportSchedule::FORMATS)],
            'recipients' => 'sometimes|array',
            'recipients.*' => 'email',
        ]);

        $schedule->update($validated);
        $schedule->calculateNextRun();

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->fresh()->load('report'),
        ]);
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule(Academy $academy, ReportSchedule $schedule): JsonResponse
    {
        if ($schedule->report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found in this academy',
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }

    /**
     * Toggle schedule active status
     */
    public function toggleScheduleStatus(Academy $academy, ReportSchedule $schedule): JsonResponse
    {
        if ($schedule->report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found in this academy',
            ], 404);
        }

        $schedule->update(['is_active' => ! $schedule->is_active]);

        return response()->json([
            'success' => true,
            'message' => $schedule->is_active ? 'Schedule activated' : 'Schedule deactivated',
            'data' => $schedule->fresh(),
        ]);
    }

    // ==================== REPORT EXPORTS ====================

    /**
     * Export a saved report
     */
    public function exportReport(Request $request, Academy $academy, SavedReport $report): JsonResponse
    {
        if ($report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found in this academy',
            ], 404);
        }

        $validated = $request->validate([
            'format' => ['required', Rule::in(ReportExport::FORMATS)],
        ]);

        // สร้างไฟล์ทันที (synchronous) จาก cached_data — ไม่พึ่ง queue
        $meta = app(ReportExportService::class)->generate($report, $validated['format']);

        $export = ReportExport::create([
            'academy_id' => $academy->id,
            'report_id' => $report->id,
            'user_id' => Auth::id(),
            'file_name' => $meta['file_name'],
            'file_path' => $meta['file_path'],
            'file_type' => $meta['file_type'],
            'file_size' => $meta['file_size'],
            'status' => ReportExport::STATUS_COMPLETED,
            'expires_at' => now()->addDays(7),
        ]);

        $this->auditLog->log('report_exported', $export, null, [
            'report' => $report->name,
            'format' => $export->file_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Export completed',
            'data' => [
                'export' => $export,
                'download_url' => $export->getDownloadUrl(),
            ],
        ], 201);
    }

    /**
     * List user's exports
     */
    public function listExports(Request $request, Academy $academy): JsonResponse
    {
        $exports = ReportExport::whereHas('report', function ($q) use ($academy) {
            $q->where('academy_id', $academy->id);
        })
            ->where('user_id', Auth::id())
            ->with('report')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    /**
     * Get export status and download URL
     */
    public function showExport(Academy $academy, ReportExport $export): JsonResponse
    {
        if ($export->report->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Export not found in this academy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'export' => $export,
                'download_url' => $export->isCompleted() ? $export->getDownloadUrl() : null,
            ],
        ]);
    }
}
