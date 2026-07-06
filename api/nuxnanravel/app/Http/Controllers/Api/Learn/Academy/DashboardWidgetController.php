<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\DashboardWidget;
use App\Models\UserDashboardLayout;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DashboardWidgetController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    // ==================== WIDGETS ====================

    /**
     * List available widgets
     */
    public function listWidgets(Request $request, Academy $academy): JsonResponse
    {
        $query = DashboardWidget::where('academy_id', $academy->id)
            ->orWhereNull('academy_id'); // Include global widgets

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('widget_type')) {
            $query->where('widget_type', $request->widget_type);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $widgets = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $widgets,
        ]);
    }

    /**
     * Create custom widget
     */
    public function createWidget(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'widget_type' => ['required', Rule::in(DashboardWidget::TYPES)],
            'category' => ['required', Rule::in(DashboardWidget::CATEGORIES)],
            'data_source' => 'required|string|max:100',
            'configuration' => 'nullable|array',
            'default_size' => 'nullable|array',
            'default_size.width' => 'nullable|integer|min:1|max:12',
            'default_size.height' => 'nullable|integer|min:1|max:4',
            'refresh_interval' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['academy_id'] = $academy->id;

        $widget = DashboardWidget::create($validated);

        $this->auditLog->log(
            'dashboard_widget_created',
            DashboardWidget::class,
            $widget->id,
            ['name' => $widget->name, 'type' => $widget->widget_type]
        );

        return response()->json([
            'success' => true,
            'message' => 'Widget created successfully',
            'data' => $widget,
        ], 201);
    }

    /**
     * Show widget details
     */
    public function showWidget(Academy $academy, DashboardWidget $widget): JsonResponse
    {
        if ($widget->academy_id && $widget->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found in this academy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $widget,
        ]);
    }

    /**
     * Update widget
     */
    public function updateWidget(Request $request, Academy $academy, DashboardWidget $widget): JsonResponse
    {
        if ($widget->academy_id && $widget->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found in this academy',
            ], 404);
        }

        // Cannot update global widgets
        if ($widget->academy_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify global widgets',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'widget_type' => ['sometimes', Rule::in(DashboardWidget::TYPES)],
            'category' => ['sometimes', Rule::in(DashboardWidget::CATEGORIES)],
            'data_source' => 'sometimes|string|max:100',
            'configuration' => 'nullable|array',
            'default_size' => 'nullable|array',
            'refresh_interval' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $widget->update($validated);

        $this->auditLog->log(
            'dashboard_widget_updated',
            DashboardWidget::class,
            $widget->id,
            ['changes' => array_keys($validated)]
        );

        return response()->json([
            'success' => true,
            'message' => 'Widget updated successfully',
            'data' => $widget->fresh(),
        ]);
    }

    /**
     * Delete widget
     */
    public function deleteWidget(Academy $academy, DashboardWidget $widget): JsonResponse
    {
        if ($widget->academy_id && $widget->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found in this academy',
            ], 404);
        }

        if ($widget->academy_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete global widgets',
            ], 403);
        }

        $widgetId = $widget->id;
        $widgetName = $widget->name;

        $widget->delete();

        $this->auditLog->log(
            'dashboard_widget_deleted',
            DashboardWidget::class,
            $widgetId,
            ['name' => $widgetName]
        );

        return response()->json([
            'success' => true,
            'message' => 'Widget deleted successfully',
        ]);
    }

    /**
     * Toggle widget status
     */
    public function toggleWidgetStatus(Academy $academy, DashboardWidget $widget): JsonResponse
    {
        if ($widget->academy_id && $widget->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget not found in this academy',
            ], 404);
        }

        if ($widget->is_active) {
            $widget->deactivate();
        } else {
            $widget->activate();
        }

        return response()->json([
            'success' => true,
            'message' => $widget->is_active ? 'Widget activated' : 'Widget deactivated',
            'data' => $widget->fresh(),
        ]);
    }

    // ==================== USER DASHBOARD LAYOUTS ====================

    /**
     * Get user's dashboard layout
     */
    public function getLayout(Request $request, Academy $academy): JsonResponse
    {
        $dashboardType = $request->get('type', 'main');

        $layout = UserDashboardLayout::where('academy_id', $academy->id)
            ->where('user_id', Auth::id())
            ->where('dashboard_type', $dashboardType)
            ->first();

        if (! $layout) {
            // Return default layout
            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard_type' => $dashboardType,
                    'layout' => [],
                    'settings' => [],
                    'is_default' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $layout,
        ]);
    }

    /**
     * Save user's dashboard layout
     */
    public function saveLayout(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_type' => ['required', Rule::in(UserDashboardLayout::DASHBOARD_TYPES)],
            'layout' => 'required|array',
            'layout.*.widget_id' => 'required|integer|exists:dashboard_widgets,id',
            'layout.*.position' => 'required|array',
            'layout.*.position.x' => 'required|integer|min:0',
            'layout.*.position.y' => 'required|integer|min:0',
            'layout.*.position.width' => 'required|integer|min:1|max:12',
            'layout.*.position.height' => 'required|integer|min:1|max:4',
            'settings' => 'nullable|array',
        ]);

        $layout = UserDashboardLayout::updateOrCreate(
            [
                'academy_id' => $academy->id,
                'user_id' => Auth::id(),
                'dashboard_type' => $validated['dashboard_type'],
            ],
            [
                'layout' => $validated['layout'],
                'settings' => $validated['settings'] ?? [],
            ]
        );

        $this->auditLog->log(
            'dashboard_layout_saved',
            UserDashboardLayout::class,
            $layout->id,
            ['dashboard_type' => $layout->dashboard_type]
        );

        return response()->json([
            'success' => true,
            'message' => 'Layout saved successfully',
            'data' => $layout,
        ]);
    }

    /**
     * Add widget to layout
     */
    public function addWidgetToLayout(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_type' => ['required', Rule::in(UserDashboardLayout::DASHBOARD_TYPES)],
            'widget_id' => 'required|exists:dashboard_widgets,id',
            'position' => 'nullable|array',
        ]);

        $layout = UserDashboardLayout::firstOrCreate(
            [
                'academy_id' => $academy->id,
                'user_id' => Auth::id(),
                'dashboard_type' => $validated['dashboard_type'],
            ],
            [
                'layout' => [],
                'settings' => [],
            ]
        );

        $layout->addWidget($validated['widget_id'], $validated['position'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Widget added to layout',
            'data' => $layout->fresh(),
        ]);
    }

    /**
     * Remove widget from layout
     */
    public function removeWidgetFromLayout(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_type' => ['required', Rule::in(UserDashboardLayout::DASHBOARD_TYPES)],
            'widget_id' => 'required|integer',
        ]);

        $layout = UserDashboardLayout::where('academy_id', $academy->id)
            ->where('user_id', Auth::id())
            ->where('dashboard_type', $validated['dashboard_type'])
            ->first();

        if (! $layout) {
            return response()->json([
                'success' => false,
                'message' => 'Layout not found',
            ], 404);
        }

        $layout->removeWidget($validated['widget_id']);

        return response()->json([
            'success' => true,
            'message' => 'Widget removed from layout',
            'data' => $layout->fresh(),
        ]);
    }

    /**
     * Update widget position in layout
     */
    public function updateWidgetPosition(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_type' => ['required', Rule::in(UserDashboardLayout::DASHBOARD_TYPES)],
            'widget_id' => 'required|integer',
            'position' => 'required|array',
            'position.x' => 'required|integer|min:0',
            'position.y' => 'required|integer|min:0',
            'position.width' => 'required|integer|min:1|max:12',
            'position.height' => 'required|integer|min:1|max:4',
        ]);

        $layout = UserDashboardLayout::where('academy_id', $academy->id)
            ->where('user_id', Auth::id())
            ->where('dashboard_type', $validated['dashboard_type'])
            ->first();

        if (! $layout) {
            return response()->json([
                'success' => false,
                'message' => 'Layout not found',
            ], 404);
        }

        $layout->updateWidgetPosition($validated['widget_id'], $validated['position']);

        return response()->json([
            'success' => true,
            'message' => 'Widget position updated',
            'data' => $layout->fresh(),
        ]);
    }

    /**
     * Reset layout to default
     */
    public function resetLayout(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'dashboard_type' => ['required', Rule::in(UserDashboardLayout::DASHBOARD_TYPES)],
        ]);

        $layout = UserDashboardLayout::where('academy_id', $academy->id)
            ->where('user_id', Auth::id())
            ->where('dashboard_type', $validated['dashboard_type'])
            ->first();

        if ($layout) {
            $layout->resetToDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Layout reset to default',
        ]);
    }
}
