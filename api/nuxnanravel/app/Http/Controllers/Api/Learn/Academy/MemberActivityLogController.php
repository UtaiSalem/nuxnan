<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\MemberActivityLog;
use Illuminate\Http\Request;

/**
 * Member Activity Log Controller - ระบบประวัติกิจกรรมสมาชิก
 */
class MemberActivityLogController extends Controller
{
    /**
     * Get activity logs for the academy
     */
    public function index(Academy $academy, Request $request)
    {
        $query = MemberActivityLog::forAcademy($academy->id)
            ->with(['user:id,name,profile_photo_path', 'targetUser:id,name,profile_photo_path'])
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->byAction($request->action);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->byCategory($request->category);
        }

        // Filter by user (performer)
        if ($request->has('user_id') && $request->user_id) {
            $query->byUser($request->user_id);
        }

        // Filter by target user
        if ($request->has('target_user_id') && $request->target_user_id) {
            $query->byTargetUser($request->target_user_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('created_at', '<=', $request->to_date.' 23:59:59');
        }

        // Default to recent 30 days
        if (! $request->has('from_date') && ! $request->has('all')) {
            $query->recent(30);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_category' => $log->action_category,
                    'description' => $log->description,
                    'icon' => $log->icon,
                    'color' => $log->color,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'avatar' => $log->user->profile_photo_path,
                    ] : null,
                    'target_user' => $log->targetUser ? [
                        'id' => $log->targetUser->id,
                        'name' => $log->targetUser->name,
                        'avatar' => $log->targetUser->profile_photo_path,
                    ] : null,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => $log->created_at->toIso8601String(),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            }),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }

    /**
     * Get activity logs for a specific member
     */
    public function getMemberLogs(Academy $academy, int $memberId, Request $request)
    {
        // Get member
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('id', $memberId)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิก',
            ], 404);
        }

        $query = MemberActivityLog::forAcademy($academy->id)
            ->where(function ($q) use ($member) {
                $q->where('target_user_id', $member->user_id)
                    ->orWhere('academy_member_id', $member->id);
            })
            ->with(['user:id,name,profile_photo_path'])
            ->orderBy('created_at', 'desc');

        $perPage = min($request->get('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'icon' => $log->icon,
                    'color' => $log->color,
                    'performed_by' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'avatar' => $log->user->profile_photo_path,
                    ] : null,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => $log->created_at->toIso8601String(),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            }),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }

    /**
     * Get activity statistics
     */
    public function getStatistics(Academy $academy, Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Count by action
        $byAction = MemberActivityLog::forAcademy($academy->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        // Count by day
        $byDay = MemberActivityLog::forAcademy($academy->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Total count
        $total = MemberActivityLog::forAcademy($academy->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Most active users
        $activeUsers = MemberActivityLog::forAcademy($academy->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('user:id,name,profile_photo_path')
            ->get()
            ->map(function ($log) {
                return [
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'avatar' => $log->user->profile_photo_path,
                    ] : null,
                    'count' => $log->count,
                ];
            });

        return response()->json([
            'success' => true,
            'statistics' => [
                'total' => $total,
                'by_action' => $byAction,
                'by_day' => $byDay,
                'active_users' => $activeUsers,
                'period_days' => $days,
            ],
        ], 200);
    }

    /**
     * Get available actions for filtering
     */
    public function getAvailableActions()
    {
        $actions = [
            ['value' => MemberActivityLog::ACTION_JOIN, 'label' => 'ส่งคำขอเข้าร่วม', 'icon' => 'mdi:account-plus'],
            ['value' => MemberActivityLog::ACTION_APPROVE, 'label' => 'อนุมัติสมาชิก', 'icon' => 'mdi:check-circle'],
            ['value' => MemberActivityLog::ACTION_REJECT, 'label' => 'ปฏิเสธสมาชิก', 'icon' => 'mdi:close-circle'],
            ['value' => MemberActivityLog::ACTION_SUSPEND, 'label' => 'ระงับสมาชิก', 'icon' => 'mdi:account-lock'],
            ['value' => MemberActivityLog::ACTION_UNSUSPEND, 'label' => 'ปลดระงับสมาชิก', 'icon' => 'mdi:account-lock-open'],
            ['value' => MemberActivityLog::ACTION_REMOVE, 'label' => 'นำออกจากสมาชิก', 'icon' => 'mdi:account-minus'],
            ['value' => MemberActivityLog::ACTION_LEAVE, 'label' => 'ออกจากสถาบัน', 'icon' => 'mdi:account-remove'],
            ['value' => MemberActivityLog::ACTION_ROLE_CHANGE, 'label' => 'เปลี่ยนบทบาท', 'icon' => 'mdi:badge-account'],
            ['value' => MemberActivityLog::ACTION_INVITE, 'label' => 'ส่งคำเชิญ', 'icon' => 'mdi:email-send'],
            ['value' => MemberActivityLog::ACTION_BULK_ACTION, 'label' => 'ดำเนินการแบบกลุ่ม', 'icon' => 'mdi:account-group'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_CREATE, 'label' => 'สร้างฝ่ายงาน', 'icon' => 'mdi:office-building-plus'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_UPDATE, 'label' => 'แก้ไขฝ่ายงาน', 'icon' => 'mdi:office-building-edit'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_DELETE, 'label' => 'ลบฝ่ายงาน', 'icon' => 'mdi:office-building-remove'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_SETUP, 'label' => 'ติดตั้งโครงสร้างฝ่ายงาน', 'icon' => 'mdi:office-building-cog'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_MEMBER_ADD, 'label' => 'เพิ่มสมาชิกเข้าฝ่ายงาน', 'icon' => 'mdi:account-plus'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_MEMBER_REMOVE, 'label' => 'นำสมาชิกออกจากฝ่ายงาน', 'icon' => 'mdi:account-minus'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_MEMBER_ROLE_CHANGE, 'label' => 'เปลี่ยนบทบาทสมาชิกในฝ่ายงาน', 'icon' => 'mdi:badge-account'],
            ['value' => MemberActivityLog::ACTION_DEPARTMENT_PERMISSION_UPDATE, 'label' => 'ปรับปรุงสิทธิ์ฝ่ายงาน', 'icon' => 'mdi:shield-edit'],
            ['value' => MemberActivityLog::ACTION_ELECTION_CREATE, 'label' => 'สร้างการเลือกตั้ง', 'icon' => 'mdi:vote'],
            ['value' => MemberActivityLog::ACTION_ELECTION_UPDATE, 'label' => 'แก้ไขการเลือกตั้ง', 'icon' => 'mdi:vote-outline'],
            ['value' => MemberActivityLog::ACTION_ELECTION_DELETE, 'label' => 'ลบการเลือกตั้ง', 'icon' => 'mdi:vote-outline'],
            ['value' => MemberActivityLog::ACTION_ELECTION_STATUS_CHANGE, 'label' => 'เปลี่ยนสถานะการเลือกตั้ง', 'icon' => 'mdi:state-machine'],
        ];

        return response()->json([
            'success' => true,
            'actions' => $actions,
        ], 200);
    }
}
