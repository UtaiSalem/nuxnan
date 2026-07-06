<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyInviteLink;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Invite Link Controller - ระบบลิงก์เชิญเข้าร่วม
 */
class InviteLinkController extends Controller
{
    /**
     * Get all invite links for the academy
     */
    public function index(Academy $academy, Request $request)
    {
        $query = AcademyInviteLink::where('academy_id', $academy->id)
            ->with(['creator:id,name', 'role:id,name,display_name'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status === 'active') {
            $query->valid();
        }

        $links = $query->get();

        return response()->json([
            'success' => true,
            'links' => $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'code' => $link->code,
                    'name' => $link->name,
                    'description' => $link->description,
                    'invite_url' => $link->invite_url,
                    'qr_code_url' => $link->qr_code_url,
                    'role' => $link->role ? [
                        'id' => $link->role->id,
                        'name' => $link->role->name,
                        'display_name' => $link->role->display_name,
                    ] : null,
                    'max_uses' => $link->max_uses,
                    'use_count' => $link->use_count,
                    'remaining_uses' => $link->remaining_uses,
                    'expires_at' => $link->expires_at?->toIso8601String(),
                    'is_active' => $link->is_active,
                    'require_approval' => $link->require_approval,
                    'status' => $link->status,
                    'created_by' => $link->creator ? [
                        'id' => $link->creator->id,
                        'name' => $link->creator->name,
                    ] : null,
                    'created_at' => $link->created_at->toIso8601String(),
                ];
            }),
        ], 200);
    }

    /**
     * Create a new invite link
     */
    public function store(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'academy_role_id' => 'nullable|exists:academy_roles,id',
            'max_uses' => 'nullable|integer|min:1',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'expires_at' => 'nullable|date|after:now',
            'require_approval' => 'nullable|boolean',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'string|max:100',
        ]);

        // Validate role belongs to academy
        if (! empty($validated['academy_role_id'])) {
            $role = AcademyRole::where('id', $validated['academy_role_id'])
                ->where('academy_id', $academy->id)
                ->first();
            if (! $role) {
                return response()->json([
                    'success' => false,
                    'message' => 'บทบาทไม่ถูกต้อง',
                ], 422);
            }
        }

        // Calculate expires_at
        $expiresAt = null;
        if (! empty($validated['expires_at'])) {
            $expiresAt = $validated['expires_at'];
        } elseif (! empty($validated['expires_in_days'])) {
            $expiresAt = now()->addDays($validated['expires_in_days']);
        }

        $link = AcademyInviteLink::create([
            'academy_id' => $academy->id,
            'created_by' => $request->user()->id,
            'name' => $validated['name'] ?? 'ลิงก์เชิญ #'.(AcademyInviteLink::where('academy_id', $academy->id)->count() + 1),
            'description' => $validated['description'] ?? null,
            'academy_role_id' => $validated['academy_role_id'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'expires_at' => $expiresAt,
            'require_approval' => $validated['require_approval'] ?? true,
            'allowed_domains' => $validated['allowed_domains'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างลิงก์เชิญเรียบร้อยแล้ว',
            'link' => [
                'id' => $link->id,
                'code' => $link->code,
                'invite_url' => $link->invite_url,
                'qr_code_url' => $link->qr_code_url,
            ],
        ], 201);
    }

    /**
     * Update an invite link
     */
    public function update(Academy $academy, AcademyInviteLink $link, Request $request)
    {
        if ($link->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'academy_role_id' => 'nullable|exists:academy_roles,id',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
            'require_approval' => 'sometimes|boolean',
        ]);

        $link->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทลิงก์เชิญเรียบร้อยแล้ว',
            'link' => $link->fresh(['role']),
        ], 200);
    }

    /**
     * Delete an invite link
     */
    public function destroy(Academy $academy, AcademyInviteLink $link)
    {
        if ($link->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ',
            ], 404);
        }

        $link->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบลิงก์เชิญเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Validate and get invite link info (public endpoint)
     */
    public function validateCode(string $code)
    {
        $link = AcademyInviteLink::where('code', $code)
            ->with(['academy:id,name,display_name,logo_url,description', 'role:id,name,display_name'])
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ หรือลิงก์ไม่ถูกต้อง',
                'valid' => false,
            ], 404);
        }

        if (! $link->isValid()) {
            $reason = match ($link->status) {
                'expired' => 'ลิงก์เชิญหมดอายุแล้ว',
                'exhausted' => 'ลิงก์เชิญถูกใช้ครบจำนวนแล้ว',
                'inactive' => 'ลิงก์เชิญถูกปิดใช้งาน',
                default => 'ลิงก์เชิญไม่สามารถใช้งานได้',
            };

            return response()->json([
                'success' => false,
                'message' => $reason,
                'valid' => false,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'academy' => [
                'id' => $link->academy->id,
                'name' => $link->academy->name,
                'display_name' => $link->academy->display_name,
                'logo' => $link->academy->logo_url,
                'description' => $link->academy->description,
            ],
            'role' => $link->role ? [
                'name' => $link->role->name,
                'display_name' => $link->role->display_name,
            ] : null,
            'require_approval' => $link->require_approval,
            'remaining_uses' => $link->remaining_uses,
            'expires_at' => $link->expires_at?->toIso8601String(),
        ], 200);
    }

    /**
     * Join academy using invite code
     */
    public function joinWithCode(string $code, Request $request)
    {
        $user = $request->user();

        $link = AcademyInviteLink::where('code', $code)
            ->with(['academy', 'role'])
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ หรือลิงก์ไม่ถูกต้อง',
            ], 404);
        }

        if (! $link->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'ลิงก์เชิญไม่สามารถใช้งานได้แล้ว',
            ], 400);
        }

        // Check email domain restriction
        if (! $link->isEmailAllowed($user->email)) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลของคุณไม่อยู่ในรายการที่อนุญาต',
            ], 403);
        }

        // Check if already a member
        $existingMember = AcademyMember::where('academy_id', $link->academy_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if ($existingMember->status === 2) { // approved
                return response()->json([
                    'success' => false,
                    'message' => 'คุณเป็นสมาชิกอยู่แล้ว',
                ], 400);
            }
            if ($existingMember->status === 1) { // pending
                return response()->json([
                    'success' => false,
                    'message' => 'คำขอเข้าร่วมของคุณอยู่ระหว่างรอการอนุมัติ',
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Create or update member
            $status = $link->require_approval ? 1 : 2; // 1=pending, 2=approved

            if ($existingMember) {
                $existingMember->update([
                    'status' => $status,
                    'academy_role_id' => $link->academy_role_id,
                ]);
                $member = $existingMember;
            } else {
                $member = AcademyMember::create([
                    'academy_id' => $link->academy_id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'academy_role_id' => $link->academy_role_id,
                ]);
            }

            // Increment use count
            $link->incrementUseCount();

            // Log activity
            MemberActivityLog::logActivity([
                'academy_id' => $link->academy_id,
                'user_id' => $user->id,
                'target_user_id' => $user->id,
                'academy_member_id' => $member->id,
                'action' => $link->require_approval ? MemberActivityLog::ACTION_JOIN : MemberActivityLog::ACTION_ACCEPT_INVITE,
                'action_category' => MemberActivityLog::CATEGORY_MEMBER,
                'new_values' => [
                    'invite_code' => $link->code,
                    'invite_name' => $link->name,
                ],
                'description' => 'เข้าร่วมผ่านลิงก์เชิญ: '.$link->name,
            ]);

            DB::commit();

            $message = $link->require_approval
                ? 'ส่งคำขอเข้าร่วมเรียบร้อยแล้ว รอการอนุมัติจากผู้ดูแล'
                : 'เข้าร่วมเรียบร้อยแล้ว';

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $status === 2 ? 'approved' : 'pending',
                'academy' => [
                    'id' => $link->academy->id,
                    'name' => $link->academy->name,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle link active status
     */
    public function toggleActive(Academy $academy, AcademyInviteLink $link)
    {
        if ($link->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ',
            ], 404);
        }

        $link->update(['is_active' => ! $link->is_active]);

        return response()->json([
            'success' => true,
            'message' => $link->is_active ? 'เปิดใช้งานลิงก์เรียบร้อยแล้ว' : 'ปิดใช้งานลิงก์เรียบร้อยแล้ว',
            'is_active' => $link->is_active,
        ], 200);
    }

    /**
     * Reset use count
     */
    public function resetUseCount(Academy $academy, AcademyInviteLink $link)
    {
        if ($link->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบลิงก์เชิญ',
            ], 404);
        }

        $link->update(['use_count' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ตจำนวนการใช้งานเรียบร้อยแล้ว',
        ], 200);
    }
}
