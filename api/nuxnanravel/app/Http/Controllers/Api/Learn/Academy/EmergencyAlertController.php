<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AlertAcknowledgement;
use App\Models\EmergencyAlert;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmergencyAlertController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * List emergency alerts for an academy
     */
    public function index(Request $request, Academy $academy)
    {
        $user = $request->user();

        $query = EmergencyAlert::where('academy_id', $academy->id)
            ->with(['creator:id,name,profile_photo_path']);

        // Filter active only for non-admins
        if (! $this->canViewAlertReports($user, $academy)) {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('alert_type', $request->type);
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $alerts = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        // Add acknowledgement status for current user
        $acknowledgedIds = AlertAcknowledgement::where('user_id', $user->id)
            ->whereIn('alert_id', $alerts->pluck('id'))
            ->pluck('response', 'alert_id')
            ->toArray();

        $alerts->getCollection()->transform(function ($alert) use ($acknowledgedIds) {
            $alert->is_acknowledged = isset($acknowledgedIds[$alert->id]);
            $alert->user_response = $acknowledgedIds[$alert->id] ?? null;

            return $alert;
        });

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'total' => $alerts->total(),
                'per_page' => $alerts->perPage(),
                'last_page' => $alerts->lastPage(),
            ],
        ]);
    }

    /**
     * Get active alerts (for notification banner)
     */
    public function active(Request $request, Academy $academy)
    {
        $alerts = EmergencyAlert::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            // CASE แทน FIELD() — FIELD() มีเฉพาะ MySQL ทำให้ endpoint นี้ 500 บน SQLite
            // และเทสต์ครอบไม่ได้เลย (แบนเนอร์ฉุกเฉินเป็นเส้นที่ต้องมีเทสต์ที่สุด)
            ->orderByRaw("CASE \"severity\" WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'message', 'alert_type', 'severity', 'created_at']);

        // Add acknowledgement status for current user
        $user = $request->user();
        $acknowledgedIds = AlertAcknowledgement::where('user_id', $user->id)
            ->whereIn('alert_id', $alerts->pluck('id'))
            ->pluck('alert_id')
            ->toArray();

        $alerts = $alerts->map(function ($alert) use ($acknowledgedIds) {
            $alert->is_acknowledged = in_array($alert->id, $acknowledgedIds);

            return $alert;
        });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get a single alert
     */
    public function show(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();
        $alert->load('creator:id,name,profile_photo_path');

        // Get user's acknowledgement if exists
        $acknowledgement = AlertAcknowledgement::where('alert_id', $alert->id)
            ->where('user_id', $user->id)
            ->first();

        $alert->user_acknowledgement = $acknowledgement;

        // Get acknowledgement stats for admins
        if ($this->canViewAlertReports($user, $academy)) {
            $alert->acknowledgement_stats = [
                'total_acknowledged' => $alert->acknowledgements()->count(),
                'safe' => $alert->acknowledgements()->where('response', 'safe')->count(),
                'need_help' => $alert->acknowledgements()->where('response', 'need_help')->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $alert,
        ]);
    }

    /**
     * Create a new emergency alert
     */
    public function store(Request $request, Academy $academy)
    {
        $user = $request->user();

        if (! $this->canManageAlerts($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'alert_type' => 'required|in:fire,earthquake,flood,lockdown,medical,weather,other',
            'severity' => 'in:low,medium,high,critical',
            'target_audience' => 'nullable|array',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'send_push' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $alert = EmergencyAlert::create([
                'academy_id' => $academy->id,
                'created_by' => $user->id,
                'title' => $request->title,
                'message' => $request->message,
                'alert_type' => $request->alert_type,
                'severity' => $request->get('severity', 'high'),
                'target_audience' => $request->target_audience,
                'send_sms' => $request->get('send_sms', false),
                'send_email' => $request->get('send_email', true),
                'send_push' => $request->get('send_push', true),
                'is_active' => true,
                'expires_at' => $request->expires_at,
            ]);

            $this->auditLog->log(
                'emergency_alert.created',
                $alert,
                null,
                $alert->toArray(),
                'emergency_alerts',
                ['academy_id' => $academy->id]
            );

            // TODO: Dispatch notification jobs for SMS, email, push
            // This would be handled by a notification service
            $deliveryStats = [
                'sms_sent' => 0,
                'email_sent' => 0,
                'push_sent' => 0,
            ];
            $alert->update(['delivery_stats' => $deliveryStats]);

            DB::commit();

            $alert->load('creator:id,name,profile_photo_path');

            return response()->json([
                'success' => true,
                'message' => 'Emergency alert created and notifications dispatched',
                'data' => $alert,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create alert: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an alert
     */
    public function update(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageAlerts($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'message' => 'string',
            'severity' => 'in:low,medium,high,critical',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldData = $alert->toArray();

            $alert->update($request->only([
                'title', 'message', 'severity', 'expires_at',
            ]));

            $this->auditLog->log(
                'emergency_alert.updated',
                $alert,
                $oldData,
                $alert->fresh()->toArray(),
                'emergency_alerts',
                ['academy_id' => $academy->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Alert updated successfully',
                'data' => $alert->fresh()->load('creator:id,name,profile_photo_path'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update alert: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate an alert
     */
    public function deactivate(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageAlerts($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $alert->deactivate();

        $this->auditLog->log(
            'emergency_alert.deactivated',
            $alert,
            ['is_active' => true],
            ['is_active' => false],
            'emergency_alerts',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert deactivated successfully',
            'data' => $alert,
        ]);
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'response' => 'in:safe,need_help',
            'channel' => 'in:app,sms,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $acknowledgement = AlertAcknowledgement::updateOrCreate(
            [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ],
            [
                'channel' => $request->get('channel', 'app'),
                'acknowledged_at' => now(),
                'response' => $request->get('response', 'safe'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
            'data' => $acknowledgement,
        ]);
    }

    /**
     * Get acknowledgement list (admin)
     */
    public function acknowledgements(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        if (! $this->canViewAlertReports($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $acknowledgements = $alert->acknowledgements()
            ->with('user:id,name,email,profile_photo_path')
            ->orderByDesc('acknowledged_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $acknowledgements->items(),
            'meta' => [
                'current_page' => $acknowledgements->currentPage(),
                'total' => $acknowledgements->total(),
                'per_page' => $acknowledgements->perPage(),
                'last_page' => $acknowledgements->lastPage(),
            ],
            'summary' => [
                'total' => $alert->acknowledgements()->count(),
                'safe' => $alert->acknowledgements()->where('response', 'safe')->count(),
                'need_help' => $alert->acknowledgements()->where('response', 'need_help')->count(),
                'by_channel' => $alert->acknowledgements()
                    ->selectRaw('channel, count(*) as count')
                    ->groupBy('channel')
                    ->pluck('count', 'channel'),
            ],
        ]);
    }

    /**
     * Get people who need help (admin)
     */
    public function needHelp(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        if (! $this->canViewAlertReports($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $needHelp = $alert->acknowledgements()
            ->where('response', 'need_help')
            ->with('user:id,name,email,profile_photo_path')
            ->orderByDesc('acknowledged_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $needHelp,
            'count' => $needHelp->count(),
        ]);
    }

    /**
     * Delete an alert
     */
    public function destroy(Request $request, Academy $academy, EmergencyAlert $alert)
    {
        if ($alert->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageAlerts($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldData = $alert->toArray();
        $alert->delete();

        $this->auditLog->log(
            'emergency_alert.deleted',
            $alert,
            $oldData,
            null,
            'emergency_alerts',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully',
        ]);
    }

    /**
     * G18 — สิทธิ์ของประกาศฉุกเฉินมาจากคีย์ `emergency.*` ไม่ใช่ `Academy::isAdmin()` อย่างเดียว
     *
     * ลำดับการปล่อยผ่านอยู่ที่ `Academy::userCan()` ที่เดียว — ตรงกับ middleware ของ route
     * ถ้าที่นี่ตรวจแคบกว่า middleware ครู/ผอ.ที่ถือคีย์จะผ่าน route มาแล้วมาโดน 403 ตรงนี้แทน
     */
    private function canViewAlertReports($user, Academy $academy): bool
    {
        return $academy->userCan($user, 'emergency.view');
    }

    private function canManageAlerts($user, Academy $academy): bool
    {
        return $academy->userCan($user, 'emergency.manage');
    }
}
