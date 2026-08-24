<?php

use App\Models\AcademyMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair two teacher membership records that keep the school's roster from
 * matching the system (found while preparing the department assignment sheet).
 *
 * 1. The bulk teacher import of 2026-07-04 wrote one membership row with a NULL
 *    user_id. Its cohort (member_code 64) was inserted in user-id order —
 *    17065, 17066, 17067, 17068, [this row], 17070, 17071 — so the row belongs
 *    to t64h005@jsm.ac.th, the only 64-series teacher with no membership at all.
 *    A NULL user_id makes the row unusable: department membership keys on
 *    user_id, so the person can never be added to a department, yet the row
 *    still inflates every member count.
 *
 * 2. t48h002@jsm.ac.th was set to status 3 (rejected) the day after the same
 *    bulk import, while every other member of his cohort stayed approved. He is
 *    on the school's official teacher roster, so the rejection is treated as a
 *    misclick and reverted to approved.
 *
 * Rows are located by email and cohort rather than by id, and every step is
 * skipped unless it matches exactly one row, so this is safe to run on a
 * database that does not have these records.
 */
return new class extends Migration
{
    private const ORPHAN_EMAIL = 't64h005@jsm.ac.th';

    private const ORPHAN_MEMBER_CODE = 64;

    private const REJECTED_EMAIL = 't48h002@jsm.ac.th';

    public function up(): void
    {
        $this->relinkOrphanMembership();
        $this->restoreRejectedTeacher();
    }

    public function down(): void
    {
        // Put the user_id back to NULL only if this migration is what filled it.
        $userId = $this->userIdByEmail(self::ORPHAN_EMAIL);
        if ($userId !== null) {
            DB::table('academy_members')
                ->where('user_id', $userId)
                ->where('member_code', self::ORPHAN_MEMBER_CODE)
                ->where('role', 'teacher')
                ->update(['user_id' => null, 'updated_at' => now()]);
        }

        $rejectedUserId = $this->userIdByEmail(self::REJECTED_EMAIL);
        if ($rejectedUserId !== null) {
            DB::table('academy_members')
                ->where('user_id', $rejectedUserId)
                ->where('status', AcademyMember::STATUS_APPROVED)
                ->update([
                    'status' => AcademyMember::STATUS_REJECTED,
                    'updated_at' => now(),
                ]);
        }
    }

    private function relinkOrphanMembership(): void
    {
        $userId = $this->userIdByEmail(self::ORPHAN_EMAIL);

        if ($userId === null) {
            return;
        }

        $orphans = DB::table('academy_members')
            ->whereNull('user_id')
            ->where('member_code', self::ORPHAN_MEMBER_CODE)
            ->where('role', 'teacher')
            ->get(['id', 'academy_id']);

        // More than one candidate means the guess is no longer unambiguous.
        if ($orphans->count() !== 1) {
            return;
        }

        $orphan = $orphans->first();

        $alreadyMember = DB::table('academy_members')
            ->where('academy_id', $orphan->academy_id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyMember) {
            return;
        }

        DB::table('academy_members')
            ->where('id', $orphan->id)
            ->update(['user_id' => $userId, 'updated_at' => now()]);
    }

    private function restoreRejectedTeacher(): void
    {
        $userId = $this->userIdByEmail(self::REJECTED_EMAIL);

        if ($userId === null) {
            return;
        }

        DB::table('academy_members')
            ->where('user_id', $userId)
            ->where('role', 'teacher')
            ->where('status', AcademyMember::STATUS_REJECTED)
            ->update([
                'status' => AcademyMember::STATUS_APPROVED,
                'updated_at' => now(),
            ]);
    }

    private function userIdByEmail(string $email): ?int
    {
        $matches = DB::table('users')->where('email', $email)->pluck('id');

        return $matches->count() === 1 ? (int) $matches->first() : null;
    }
};
