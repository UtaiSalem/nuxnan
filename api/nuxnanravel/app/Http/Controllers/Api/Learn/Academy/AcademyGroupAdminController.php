<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupAdmin;
use App\Models\AcademyMember;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AcademyGroupAdminController extends Controller
{
    public function index(AcademyGroup $academyGroup)
    {
        return response()->json([
            'success' => true,
            'admins' => AcademyGroupAdmin::with([
                'user:id,name,email,profile_photo_path',
                'appointer:id,name',
            ])
                ->where('academy_group_id', $academyGroup->id)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request, AcademyGroup $academyGroup)
    {
        $this->authorizeManage($academyGroup);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'nullable|string|in:leader,co_leader,advisor',
        ]);

        $isAcademyMember = AcademyMember::where('academy_id', $academyGroup->academy_id)
            ->where('user_id', $validated['user_id'])
            ->where('status', 2)
            ->exists();

        if (! $isAcademyMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้ยังไม่ได้เป็นสมาชิกที่ได้รับการอนุมัติของสถาบันการศึกษา',
            ], 422);
        }

        $exists = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้เป็นหัวหน้าของส่วนงานนี้อยู่แล้ว',
            ], 422);
        }

        $admin = AcademyGroupAdmin::create([
            'academy_group_id' => $academyGroup->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'] ?? 'leader',
            'appointed_by' => auth()->id(),
        ])->load([
            'user:id,name,email,profile_photo_path',
            'appointer:id,name',
        ]);

        $academyGroup->load('academy');
        $academyName = $academyGroup->academy?->name ?? 'academy';
        app(NotificationService::class)->send([
            'user_id' => $validated['user_id'],
            'sender_id' => auth()->id(),
            'type' => Notification::TYPE_GROUP_ADMIN_ADDED,
            'content' => "คุณได้รับการแต่งตั้งเป็นหัวหน้าของส่วนงาน \"{$academyGroup->name}\"",
            'action_url' => "/academies/{$academyName}/groups/{$academyGroup->id}",
            'related_id' => $academyGroup->id,
        ]);

        return response()->json([
            'success' => true,
            'admin' => $admin,
        ], 201);
    }

    public function updateRole(Request $request, AcademyGroup $academyGroup)
    {
        $this->authorizeManage($academyGroup);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:leader,co_leader,advisor',
        ]);

        $admin = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบหัวหน้าของส่วนงานนี้',
            ], 404);
        }

        $admin->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'admin' => $admin->load('user:id,name,email,profile_photo_path'),
        ]);
    }

    public function destroy(Request $request, AcademyGroup $academyGroup)
    {
        $this->authorizeManage($academyGroup);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $deleted = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $validated['user_id'])
            ->delete();

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบหัวหน้ารายนี้ในส่วนงาน',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'นำหัวหน้าออกแล้ว',
        ]);
    }

    private function authorizeManage(AcademyGroup $academyGroup): void
    {
        $academy = $academyGroup->academy;
        $user = auth()->user();

        if (! $academy || ! $user || ! $academy->isAdmin($user)) {
            abort(response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์จัดการหัวหน้าส่วนงานนี้',
            ], 403));
        }
    }
}
