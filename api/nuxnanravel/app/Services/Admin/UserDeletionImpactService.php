<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionImpactService
{
    /**
     * วิเคราะห์ผลกระทบก่อนลบ user
     * คืน array ที่มี blockers, warnings, can_delete
     */
    public function getImpact(User $user): array
    {
        $blockers = [];
        $warnings = [];

        // ─── BLOCKERS ────────────────────────────────────────────

        // 1. เป็น sole owner ของ academy ที่มี member
        $soleOwnedAcademies = DB::table('academies as a')
            ->where('a.user_id', $user->id)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('academy_members as am')
                    ->whereColumn('am.academy_id', 'a.id');
            })
            ->where(function ($q) use ($user) {
                // ไม่มี admin คนอื่น (นอกจากตัวเอง)
                $q->whereNotExists(function ($q2) use ($user) {
                    $q2->select(DB::raw(1))
                        ->from('academy_admins as aa')
                        ->whereColumn('aa.academy_id', 'a.id')
                        ->where('aa.user_id', '!=', $user->id);
                });
            })
            ->select('a.id', 'a.name')
            ->get();

        if ($soleOwnedAcademies->isNotEmpty()) {
            $blockers[] = [
                'type' => 'sole_academy_owner',
                'message' => 'เป็นเจ้าของ Academy เพียงคนเดียว ต้อง transfer หรือ archive ก่อน',
                'count' => $soleOwnedAcademies->count(),
                'items' => $soleOwnedAcademies->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->toArray(),
            ];
        }

        // 2. เป็น sole owner ของ course ที่มี member
        $soleOwnedCourses = DB::table('courses as c')
            ->where('c.user_id', $user->id)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('course_members as cm')
                    ->whereColumn('cm.course_id', 'c.id')
                    ->whereIn('cm.role', [1, 2]); // student, student_leader
            })
            ->where(function ($q) use ($user) {
                // ไม่มี admin/teacher คนอื่น
                $q->whereNotExists(function ($q2) use ($user) {
                    $q2->select(DB::raw(1))
                        ->from('course_members as cm2')
                        ->whereColumn('cm2.course_id', 'c.id')
                        ->whereIn('cm2.role', [3, 4]) // teacher, admin
                        ->where('cm2.user_id', '!=', $user->id);
                });
            })
            ->select('c.id', 'c.name')
            ->get();

        if ($soleOwnedCourses->isNotEmpty()) {
            $blockers[] = [
                'type' => 'sole_course_owner',
                'message' => 'เป็นเจ้าของ Course เพียงคนเดียว ต้อง transfer หรือ archive ก่อน',
                'count' => $soleOwnedCourses->count(),
                'items' => $soleOwnedCourses->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->toArray(),
            ];
        }

        // 3. มี wallet deposit ที่ยัง pending
        $pendingDeposits = DB::table('wallet_deposit_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingDeposits > 0) {
            $blockers[] = [
                'type' => 'pending_deposit',
                'message' => "มีคำขอเติมเงินที่ยังค้างอยู่ {$pendingDeposits} รายการ ต้อง settle ก่อน",
                'count' => $pendingDeposits,
            ];
        }

        // 4. FK RESTRICT tables — ถ้ามี records จะทำให้ delete fail
        $restrictBlockers = $this->checkFkRestrictTables($user->id);
        if (! empty($restrictBlockers)) {
            $blockers[] = [
                'type' => 'fk_restrict',
                'message' => 'มีข้อมูลระบบที่ผูกกับผู้ใช้นี้โดยตรง ต้องแก้ไขก่อนลบ',
                'tables' => $restrictBlockers,
            ];
        }

        // ─── WARNINGS ────────────────────────────────────────────

        // Cascade delete — ข้อมูลจะหายถาวร
        $cascadeCounts = [
            'wallet_transactions' => DB::table('wallet_transactions')->where('user_id', $user->id)->count(),
            'points_transactions' => DB::table('points_transactions')->where('user_id', $user->id)->count(),
            'course_purchases' => DB::table('course_purchases')
                ->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id)
                ->count(),
            'user_achievements' => DB::table('user_achievements')->where('user_id', $user->id)->count(),
            'user_rewards' => DB::table('user_rewards')->where('user_id', $user->id)->count(),
        ];

        $totalCascade = array_sum($cascadeCounts);
        if ($totalCascade > 0) {
            $warnings[] = [
                'type' => 'cascade_delete',
                'message' => "ข้อมูล {$totalCascade} รายการจะถูกลบถาวร (ประวัติการเงิน, คะแนน, ความสำเร็จ)",
                'counts' => array_filter($cascadeCounts), // เอาเฉพาะ > 0
            ];
        }

        // Orphan — ข้อมูลค้างแต่ไม่มีเจ้าของ
        $orphanCounts = [
            'courses' => DB::table('courses')->where('user_id', $user->id)->count(),
            'academies' => DB::table('academies')->where('user_id', $user->id)->count(),
            'posts' => DB::table('posts')->where('user_id', $user->id)->count(),
            'lessons' => DB::table('lessons')->where('user_id', $user->id)->count(),
        ];

        $totalOrphan = array_sum($orphanCounts);
        if ($totalOrphan > 0) {
            $warnings[] = [
                'type' => 'orphan_records',
                'message' => "ข้อมูล {$totalOrphan} รายการจะยังอยู่ใน DB แต่ไม่มีเจ้าของ (courses, posts, lessons)",
                'counts' => array_filter($orphanCounts),
            ];
        }

        return [
            'blockers' => $blockers,
            'warnings' => $warnings,
            'can_delete' => empty($blockers),
            'summary' => [
                'total_cascade' => $totalCascade,
                'total_orphan' => $totalOrphan,
            ],
        ];
    }

    /**
     * ตรวจตาราง FK RESTRICT ที่จะทำให้ delete fail ถ้ามี records
     */
    private function checkFkRestrictTables(int $userId): array
    {
        $problematic = [];

        $checks = [
            'school_attendances' => ['column' => 'created_by',  'label' => 'บันทึกเช็คชื่อโรงเรียน'],
            'library_borrowings' => ['column' => 'handled_by',  'label' => 'การยืมหนังสือห้องสมุด'],
            'asset_maintenance_logs' => ['column' => 'requested_by', 'label' => 'บันทึกซ่อมบำรุงทรัพย์สิน'],
            'course_permissions' => ['column' => 'granted_by',  'label' => 'สิทธิ์พิเศษของ Course'],
        ];

        foreach ($checks as $table => $info) {
            // ตรวจว่าตารางมีอยู่จริงก่อน (ป้องกัน error ถ้า migration ยังไม่รัน)
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->where($info['column'], $userId)->count();
            if ($count > 0) {
                $problematic[] = [
                    'table' => $table,
                    'column' => $info['column'],
                    'label' => $info['label'],
                    'count' => $count,
                ];
            }
        }

        return $problematic;
    }
}
