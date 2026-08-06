<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * คืนบัญชีผู้ใช้ให้นักเรียนเลข 12247 (เด็กชาย อาดิษ ประสารการ)
 *
 * ผลพวงสุดท้ายของเคสชื่อสลับ — ตอนที่ระเบียน 12247 ยังถูกสวมชื่อ "อลียา ยังปากน้ำ"
 * ระบบจึงมีบัญชีชื่ออลียาสองใบ (s12246@ กับ s12247@) ผู้ดูแลจึงลบใบ s12247@
 * ทิ้งด้วยเหตุผล "บัญชีซ้ำซ้อน" เมื่อ 2026-08-06 10:13 ทั้งที่มันคือบัญชีของอาดิษ
 *
 * ร่องรอยใน admin_user_deletion_audits:
 *   #34  10:13:36  user 16619  s12247@jariyathum.ac.th  "บัญชีซ้ำซ้อน"
 *   #35  10:19:59  user 17502  s12247@jariyathum.ac.th  "ผิดพลาด"
 *
 * ใบที่ต้องคืนคือใบแรก เพราะเป็นใบที่ students.user_id ชี้อยู่ สร้างมาพร้อม import
 * ตั้งแต่ 2026-06-20 และยืนยันอีเมลแล้ว ส่วนใบที่สองถูกสร้างใหม่หลังลบใบแรก 4 นาที
 * แล้วผู้ดูแลลบเองใน 6 นาทีถัดมาโดยระบุว่า "ผิดพลาด" — ไม่ผูกกับนักเรียนคนใด
 * ทั้งสองใบไม่มีคะแนน กระเป๋าเงิน หรือกิจกรรมใดๆ จึงไม่มีข้อมูลที่ต้องรวม
 *
 * users.username และ users.email เป็น UNIQUE ที่ไม่รู้จัก soft delete จึงต้อง
 * ปลดชื่อออกจากใบที่ถูกลบก่อน แล้วค่อยคืนให้ใบจริง
 *
 * ชื่อที่ใช้คืนดึงจากระเบียนนักเรียนโดยตรง ไม่ได้เขียนค่าไว้ตายตัว — migration
 * 140000 ซ่อมชื่อในตาราง students ไปแล้ว ตัวนี้จึงอ่านค่าที่ถูกต้องมาใช้ต่อได้
 */
return new class extends Migration
{
    private const BACKUP = 'bk_user_restore_20260806';

    private const STUDENT = ['code' => '12247', 'citizen_id' => '1909803960954'];

    /** ใช้เมื่อหา audit การลบไม่เจอ */
    private const FALLBACK_EMAIL = 's12247@jariyathum.ac.th';

    public function up(): void
    {
        $this->createBackupTable();

        $student = DB::table('students')
            ->where('student_id', self::STUDENT['code'])
            ->where('citizen_id', self::STUDENT['citizen_id'])
            ->first();

        if (! $student || ! $student->user_id) {
            Log::info('user-restore-20260806: ไม่พบนักเรียนหรือนักเรียนไม่ได้ผูกกับบัญชีใด ข้าม migration');

            return;
        }

        $user = DB::table('users')->where('id', $student->user_id)->first();

        if (! $user) {
            Log::warning('user-restore-20260806: ไม่พบบัญชีที่นักเรียนผูกอยู่', ['user_id' => $student->user_id]);

            return;
        }

        if (! $user->deleted_at) {
            return; // คืนไปแล้ว
        }

        $displayName = trim($student->first_name_th.' '.$student->last_name_th);

        if ($displayName === '') {
            Log::warning('user-restore-20260806: ระเบียนนักเรียนไม่มีชื่อ ข้าม migration');

            return;
        }

        DB::transaction(function () use ($user, $displayName) {
            $this->releaseNameFromOrphan($user->id, $displayName);
            $this->restore($user, $displayName);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        DB::transaction(function () {
            foreach (DB::table(self::BACKUP)->orderByDesc('id')->get() as $row) {
                $values = json_decode($row->old_values, true);
                if (is_array($values) && $values !== []) {
                    DB::table($row->table_name)->where('id', $row->row_id)->update($values);
                }
            }
        });

        Schema::dropIfExists(self::BACKUP);
    }

    // ================================================================

    /**
     * บัญชีที่ถูกลบและไม่ได้ผูกกับนักเรียนคนใด แต่ยังจอง username ของอาดิษอยู่
     * เปลี่ยนเป็นรูปแบบกลางเพื่อปลดชื่อ ไม่ปลุกบัญชีนั้นกลับมา
     */
    private function releaseNameFromOrphan(int $keepUserId, string $displayName): void
    {
        $orphans = DB::table('users')
            ->where('username', $displayName)
            ->where('id', '!=', $keepUserId)
            ->whereNotNull('deleted_at')
            ->get();

        foreach ($orphans as $orphan) {
            if (DB::table('students')->where('user_id', $orphan->id)->exists()) {
                Log::warning('user-restore-20260806: บัญชีที่จองชื่ออยู่ยังผูกกับนักเรียน ไม่แตะ', ['user_id' => $orphan->id]);

                continue;
            }

            $replacement = 'deleted_'.$orphan->id;

            if (DB::table('users')->where('username', $replacement)->exists()) {
                continue;
            }

            $this->backup('users', $orphan->id, ['username' => $orphan->username, 'updated_at' => $orphan->updated_at]);

            DB::table('users')->where('id', $orphan->id)->update([
                'username' => $replacement,
                'updated_at' => now(),
            ]);
        }
    }

    private function restore(object $user, string $displayName): void
    {
        $this->backup('users', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'deleted_at' => $user->deleted_at,
            'deleted_by' => $user->deleted_by,
            'deletion_reason' => $user->deletion_reason,
            'anonymized_at' => $user->anonymized_at,
            'updated_at' => $user->updated_at,
        ]);

        $updates = [
            'name' => $displayName,
            'deleted_at' => null,
            'deleted_by' => null,
            'deletion_reason' => null,
            'anonymized_at' => null,
            'updated_at' => now(),
        ];

        // username / email เป็น UNIQUE ที่ไม่รู้จัก soft delete — คืนเฉพาะที่ว่างจริง
        if (! DB::table('users')->where('username', $displayName)->where('id', '!=', $user->id)->exists()) {
            $updates['username'] = $displayName;
        } else {
            Log::warning('user-restore-20260806: username ถูกใช้อยู่ คงค่าเดิมไว้', ['user_id' => $user->id]);
        }

        $email = $this->originalEmail($user->id);

        if ($email && ! DB::table('users')->where('email', $email)->where('id', '!=', $user->id)->exists()) {
            $updates['email'] = $email;
        } else {
            Log::warning('user-restore-20260806: อีเมลเดิมถูกใช้อยู่หรือหาไม่เจอ คงค่าเดิมไว้', ['user_id' => $user->id]);
        }

        DB::table('users')->where('id', $user->id)->update($updates);
    }

    /** อีเมลก่อนถูกลบ อ่านจาก audit การลบ ถ้าไม่มีให้ใช้ค่าสำรอง */
    private function originalEmail(int $userId): ?string
    {
        if (! Schema::hasTable('admin_user_deletion_audits')) {
            return self::FALLBACK_EMAIL;
        }

        $recorded = DB::table('admin_user_deletion_audits')
            ->where('deleted_user_id', $userId)
            ->orderByDesc('id')
            ->value('deleted_user_email');

        return $recorded ?: self::FALLBACK_EMAIL;
    }

    private function createBackupTable(): void
    {
        if (Schema::hasTable(self::BACKUP)) {
            return;
        }

        Schema::create(self::BACKUP, function ($table) {
            $table->id();
            $table->string('table_name', 64);
            $table->unsignedBigInteger('row_id');
            $table->json('old_values');
            $table->timestamp('captured_at')->useCurrent();
            $table->index(['table_name', 'row_id']);
        });
    }

    private function backup(string $table, int $rowId, array $oldValues): void
    {
        DB::table(self::BACKUP)->insert([
            'table_name' => $table,
            'row_id' => $rowId,
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'captured_at' => now(),
        ]);
    }
};
