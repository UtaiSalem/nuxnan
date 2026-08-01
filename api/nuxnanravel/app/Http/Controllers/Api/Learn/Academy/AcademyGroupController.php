<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Constants\AcademyGroupPermissions;
use App\Constants\AcademyGroupTypes;
use App\Http\Controllers\Controller;
use App\Http\Resources\Play\ActivityResource;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupAdmin;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademyGroupController extends Controller
{
    // Middleware is handled in route definitions (routes/learn/academy.php)

    /**
     * Display a listing of the resource.
     */
    public function index(Academy $academy)
    {
        return response()->json([
            'success' => true,
            'groups' => $academy->academyGroups()->withCount('members')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', 'string', Rule::in(AcademyGroupTypes::keys())],
            'parent_id' => 'nullable|integer|exists:academy_groups,id',
            'settings' => 'nullable|array',
        ]);

        $group = $academy->academyGroups()->create($validated);

        // Auto-seed Default Permissions
        foreach (AcademyGroupPermissions::PERMISSIONS as $key => $meta) {
            AcademyGroupPermission::create([
                'academy_group_id' => $group->id,
                'permission_key' => $key,
                'enabled' => $meta['default'],
            ]);
        }

        return response()->json([
            'success' => true,
            'group' => $group,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademyGroup $academyGroup)
    {
        return response()->json([
            'success' => true,
            'group' => $academyGroup->load('academy'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademyGroup $academyGroup)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => ['nullable', 'string', Rule::in(AcademyGroupTypes::keys())],
            'parent_id' => 'nullable|integer|exists:academy_groups,id',
            'settings' => 'nullable|array',
        ]);

        $academyGroup->update($validated);

        return response()->json([
            'success' => true,
            'group' => $academyGroup,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademyGroup $academyGroup)
    {
        $academyGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully',
        ]);
    }

    /**
     * Get all members of a group.
     */
    public function getMembers(AcademyGroup $academyGroup)
    {
        $members = $academyGroup->members()
            ->wherePivot('status', 2)
            ->with('profile')
            ->get();

        return response()->json([
            'success' => true,
            'members' => $members,
            'members_count' => $members->count(),
        ]);
    }

    /**
     * Add a member to a group.
     */
    public function addMember(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|in:student,teacher,admin',
        ]);

        $userId = $validated['user_id'];

        // ⭐ ตรวจสอบว่าเป็นสมาชิกของสถาบันที่ได้รับอนุมัติแล้วหรือไม่ (status = 2)
        $isAcademyMember = AcademyMember::where('academy_id', $academyGroup->academy_id)
            ->where('user_id', $userId)
            ->where('status', 2) // 2 = Approved
            ->exists();

        if (! $isAcademyMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้ยังไม่ได้เป็นสมาชิกที่ได้รับการอนุมัติของสถาบันการศึกษา',
            ], 422);
        }

        // Check if already a member (approved or pending)
        $existing = AcademyGroupMember::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->status === 1) {
                // Approve the pending request
                $existing->update([
                    'role' => $validated['role'] ?? 'student',
                    'status' => 2,
                    'invited_by' => auth()->id(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ผู้ใช้นี้เป็นสมาชิกของกลุ่มอยู่แล้ว',
                ], 400);
            }
        } else {
            $academyGroup->members()->attach($userId, [
                'role' => $validated['role'] ?? 'student',
                'status' => 2,
                'invited_by' => auth()->id(),
            ]);
        }

        $academyGroup->load('academy');
        $academyName = $academyGroup->academy?->name ?? 'academy';
        app(NotificationService::class)->send([
            'user_id' => $userId,
            'sender_id' => auth()->id(),
            'type' => Notification::TYPE_GROUP_MEMBER_ADDED,
            'content' => "คุณได้รับการเพิ่มเข้าร่วมส่วนงาน \"{$academyGroup->name}\"",
            'action_url' => "/academies/{$academyName}/groups/{$academyGroup->id}",
            'related_id' => $academyGroup->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Remove a member from a group.
     */
    public function removeMember(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $academyGroup->members()->detach($validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'ลบสมาชิกออกจากกลุ่มเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Update a member's role in the group.
     */
    public function updateMemberRole(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:student,teacher,admin',
        ]);

        $academyGroup->members()->updateExistingPivot($validated['user_id'], [
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตบทบาทสมาชิกเรียบร้อยแล้ว',
        ]);
    }

    /**
     * POST /groups/{academyGroup}/join-request
     * User self-requests to join group
     */
    public function requestJoin(Request $request, AcademyGroup $academyGroup)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ต้องเป็น academy member ก่อน
        $isAcademyMember = AcademyMember::where('academy_id', $academyGroup->academy_id)
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->exists();
        if (! $isAcademyMember) {
            return response()->json(['success' => false, 'message' => 'ต้องเป็นสมาชิกของโรงเรียนก่อน'], 422);
        }

        // ป้องกันซ้ำ
        $existing = AcademyGroupMember::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => $existing->status === 1 ? 'ส่งคำขอเข้าร่วมแล้ว รอการอนุมัติ' : 'คุณเป็นสมาชิกของกลุ่มนี้อยู่แล้ว',
            ], 422);
        }

        $member = AcademyGroupMember::create([
            'academy_group_id' => $academyGroup->id,
            'user_id' => $user->id,
            'role' => 'student',
            'status' => 1, // pending
        ]);

        // Send notification to group admins
        $groupAdminIds = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->pluck('user_id');

        $academyGroup->load('academy');
        $academyName = $academyGroup->academy?->name ?? 'academy';

        $notifications = [];
        foreach ($groupAdminIds as $adminId) {
            $notifications[] = [
                'user_id' => $adminId,
                'sender_id' => $user->id,
                'type' => Notification::TYPE_GROUP_MEMBER_ADDED,
                'content' => "{$user->name} ขอเข้าร่วมส่วนงาน \"{$academyGroup->name}\"",
                'action_url' => "/academies/{$academyName}/groups/{$academyGroup->id}?tab=members",
                'related_id' => $academyGroup->id,
            ];
        }
        app(NotificationService::class)->sendBulk($notifications);

        return response()->json(['success' => true, 'request' => $member], 201);
    }

    /**
     * GET /groups/{academyGroup}/pending-members
     */
    public function pendingMembers(AcademyGroup $academyGroup)
    {
        $pending = AcademyGroupMember::with('user:id,name,email,profile_photo_path')
            ->where('academy_group_id', $academyGroup->id)
            ->where('status', 1)
            ->get();

        return response()->json(['success' => true, 'data' => $pending]);
    }

    /**
     * POST /groups/{academyGroup}/approve/{member}
     */
    public function approveMember(Request $request, AcademyGroup $academyGroup, AcademyGroupMember $member)
    {
        abort_unless($member->academy_group_id === $academyGroup->id, 404);
        $member->update(['status' => 2]);

        // Notify user
        $academyGroup->load('academy');
        $academyName = $academyGroup->academy?->name ?? 'academy';

        app(NotificationService::class)->send([
            'user_id' => $member->user_id,
            'sender_id' => auth()->id(),
            'type' => Notification::TYPE_GROUP_MEMBER_ADDED,
            'content' => "คำขอเข้าร่วมส่วนงาน \"{$academyGroup->name}\" ของคุณได้รับการอนุมัติแล้ว",
            'action_url' => "/academies/{$academyName}/groups/{$academyGroup->id}",
            'related_id' => $academyGroup->id,
        ]);

        return response()->json(['success' => true, 'member' => $member]);
    }

    /**
     * POST /groups/{academyGroup}/reject/{member}
     */
    public function rejectMember(Request $request, AcademyGroup $academyGroup, AcademyGroupMember $member)
    {
        abort_unless($member->academy_group_id === $academyGroup->id, 404);
        $member->delete(); // delete request to allow user to request again later

        return response()->json(['success' => true]);
    }

    /**
     * Get groups by type (departments, classrooms, or clubs).
     */
    public function getByType(Academy $academy, string $type)
    {
        if (! AcademyGroupTypes::exists($type)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group type',
            ], 400);
        }

        $groups = $academy->academyGroups()
            ->where('type', $type)
            ->withCount('members')
            ->get();

        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    /**
     * Get groups for a specific user.
     */
    public function getUserGroups(User $user)
    {
        $groups = AcademyGroup::whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->withCount('members')->take(20)->get();

        return response()->json([
            'success' => true,
            'groups' => $groups->map(function ($group) use ($user) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => (string) $group->id,
                    'description' => $group->description,
                    'members_count' => $group->members_count,
                    'category' => $group->type,
                    'privacy' => 'public',
                    'is_member' => true,
                    'is_admin' => $group->admins()->where('users.id', $user->id)->exists(),
                ];
            }),
        ]);
    }

    /**
     * Reorder academy groups
     */
    public function reorder(Academy $academy, Request $request)
    {
        try {
            // Permission check (Academy Admin or Super Admin)
            if (! $academy->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์จัดลำดับกลุ่มในสถาบันนี้',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'groups' => 'required|array|min:1',
                'groups.*' => 'required|integer|exists:academy_groups,id',
            ]);

            // Verify group IDs belong to this academy and all are present
            $academyGroupIds = $academy->academyGroups()->pluck('id')->toArray();
            $incomingIds = $validated['groups'];

            if (count($incomingIds) !== count($academyGroupIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'จำนวนกลุ่มไม่ถูกต้อง กรุณาส่งรายการกลุ่มทั้งหมดในสถาบันนี้',
                ], 422);
            }

            if (count($incomingIds) !== count(array_unique($incomingIds))) {
                return response()->json([
                    'success' => false,
                    'message' => 'มีไอดีกลุ่มซ้ำในรายการที่ส่งมา',
                ], 422);
            }

            foreach ($incomingIds as $id) {
                if (! in_array($id, $academyGroupIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => "กลุ่ม ID {$id} ไม่ได้อยู่ในสถาบันนี้",
                    ], 422);
                }
            }

            // Perform reorder in transaction
            DB::transaction(function () use ($incomingIds) {
                foreach ($incomingIds as $index => $id) {
                    AcademyGroup::where('id', $id)->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'บันทึกลำดับกลุ่มสำเร็จ',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error reordering academy groups: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการจัดลำดับกลุ่ม',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /groups/{academyGroup}/posts
     * List posts where posted_as_group_id = group.id, paginated.
     */
    public function posts(Request $request, AcademyGroup $academyGroup)
    {
        $perPage = (int) $request->input('per_page', 10);

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
            ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academyGroup) {
                $query->where('posted_as_group_id', $academyGroup->id);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => ActivityResource::collection($activities->items()),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'next_page_url' => $activities->nextPageUrl(),
                'prev_page_url' => $activities->previousPageUrl(),
            ],
        ]);
    }

    /**
     * GET /groups/{academyGroup}/stats
     */
    public function stats(AcademyGroup $academyGroup)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'members_count' => AcademyGroupMember::where('academy_group_id', $academyGroup->id)->count(),
                'admins_count' => AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)->count(),
                'posts_count' => AcademyPost::where('posted_as_group_id', $academyGroup->id)->count(),
                'created_at' => $academyGroup->created_at,
            ],
        ]);
    }

    /**
     * GET /academies/{academy}/postable-groups
     * Returns groups within this academy where the current user is admin/member
     * AND the group has can_post = true.
     */
    public function postableForUser(Request $request, Academy $academy)
    {
        $userId = $request->user()?->id;
        if (! $userId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // 1. group ids ที่ user เป็น admin
        $adminGroupIds = AcademyGroupAdmin::where('user_id', $userId)
            ->pluck('academy_group_id');

        // 2. group ids ที่ user เป็น member
        $memberGroupIds = AcademyGroupMember::where('user_id', $userId)
            ->pluck('academy_group_id');

        $candidateIds = $adminGroupIds->merge($memberGroupIds)->unique();

        if ($candidateIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // 3. filter: must belong to this academy AND have can_post enabled
        $postableIds = AcademyGroupPermission::whereIn('academy_group_id', $candidateIds)
            ->where('permission_key', 'can_post')
            ->where('enabled', true)
            ->pluck('academy_group_id');

        $groups = AcademyGroup::whereIn('id', $postableIds)
            ->where('academy_id', $academy->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'description']);

        // attach role (admin/member) per group สำหรับ UI ใส่ badge
        $roleMap = [];
        foreach ($adminGroupIds as $gid) {
            $roleMap[$gid] = 'admin';
        }
        foreach ($memberGroupIds as $gid) {
            if (! isset($roleMap[$gid])) {
                $roleMap[$gid] = 'member';
            }
        }

        $payload = $groups->map(function ($g) use ($roleMap) {
            return [
                'id' => $g->id,
                'name' => $g->name,
                'type' => $g->type,
                'type_meta' => AcademyGroupTypes::get($g->type),
                'role' => $roleMap[$g->id] ?? null,
            ];
        });

        return response()->json(['success' => true, 'data' => $payload]);
    }
}
