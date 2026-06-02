<?php

namespace App\Services\Admin;

use App\Models\AdminUserDeletionAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserDeletionService
{
    public function __construct(
        private UserDeletionImpactService $impactService
    ) {}

    /**
     * Soft delete + anonymize user
     * ใช้เป็น default action ของปุ่ม "ลบผู้ใช้" ใน admin
     */
    public function softDelete(User $user, User $deletedBy, string $reason): array
    {
        // ─── ตรวจ blockers ──────────────────────────────────────
        $impact = $this->impactService->getImpact($user);

        if (! $impact['can_delete']) {
            throw ValidationException::withMessages([
                'blockers' => ['ไม่สามารถลบได้ กรุณาจัดการปัญหาต่อไปนี้ก่อน'],
            ])->status(422);
        }

        // ─── Snapshot ก่อน anonymize ────────────────────────────
        $snapshot = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'roles' => $user->roles()->pluck('name')->toArray(),
            'pp'         => $user->pp,
            'wallet'     => $user->wallet,
            'created_at' => $user->created_at instanceof \Carbon\Carbon ? $user->created_at->toDateTimeString() : $user->created_at,
            ];

        DB::transaction(function () use ($user, $deletedBy, $reason, $impact, $snapshot) {

            // Lock row ป้องกัน race condition
            User::withTrashed()->lockForUpdate()->find($user->id);

            // ─── Anonymize PII พร้อมกันเลย ──────────────────────
            // (ต้อง anonymize ก่อน soft delete ไม่งั้น email unique constraint ยังค้าง)
            $user->name = 'Deleted User';
            $user->email = 'deleted_'.$user->id.'@nuxnan.del';
            $user->phone_number = null;
            $user->google_id = null;
            $user->facebook_id = null;
            $user->twitter_id = null;
            $user->linkedin_id = null;
            $user->github_id = null;
            $user->profile_photo_path = null;
            $user->deleted_by = $deletedBy->id;
            $user->deletion_reason = $reason;
            $user->anonymized_at = now();
            $user->save();

            // ─── Soft delete ────────────────────────────────────
            $user->delete();  // sets deleted_at (SoftDeletes trait)

            // ─── Audit log ──────────────────────────────────────
            AdminUserDeletionAudit::create([
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $snapshot['email'],  // เก็บ email จริงไว้ใน audit
                'deleted_by' => $deletedBy->id,
                'mode' => 'soft_delete',
                'reason' => $reason,
                'user_snapshot' => $snapshot,
                'impact_snapshot' => $impact,
            ]);
        });

        // ─── JWT invalidation ───────────────────────────────────
        // SoftDeletes global scope ทำให้ User::find() คืน null → auth:api reject อัตโนมัติ
        // ไม่ต้อง blacklist เพิ่ม แต่ log ไว้เพื่อ audit
        Log::info('User soft-deleted', [
            'user_id' => $user->id,
            'deleted_by' => $deletedBy->id,
        ]);

        return [
            'message' => 'ปิดใช้งานบัญชีสำเร็จ',
            'impact_summary' => $impact['summary'],
        ];
    }

    /**
     * Restore user ที่ถูก soft delete กลับมา
     */
    public function restore(User $user, User $restoredBy): void
    {
        DB::transaction(function () use ($user, $restoredBy) {
            $user->restore();  // ล้าง deleted_at
            $user->update([
                'deleted_by' => null,
                'deletion_reason' => null,
                // หมายเหตุ: anonymized_at ยังเก็บไว้ เพราะ email/name ถูก anonymize ไปแล้ว
                // admin ต้องอัพเดต email/name ใหม่เองผ่านหน้า edit
            ]);

            AdminUserDeletionAudit::create([
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
                'deleted_by' => $restoredBy->id,
                'mode' => 'restore',
                'reason' => 'Admin restore',
            ]);
        });

        Log::info('User restored', [
            'user_id' => $user->id,
            'restored_by' => $restoredBy->id,
        ]);
    }
}
