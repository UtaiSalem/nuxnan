<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SET-S5 — ทำให้สวิตช์ของโรงเรียนมีผลจริง
 *
 * 1) reconcile `join_mode` กับ `auto_accept_members` ก่อน drop
 *    แถวไหนสองค่าไม่ตรงกัน ให้เชื่อ `auto_accept_members` เพราะ `updateSettings()` เขียนสองค่าพร้อมกันเสมอ
 *    แถวที่ขัดกันจึงมาจาก `AcademyController::store()` ที่เขียนแค่ `auto_accept_members` อย่างเดียว
 * 2) reset `show_member_list` / `show_course_list` เป็น 1 ทุกแถว
 *    ค่าเดิมถูกตั้งตอนที่สวิตช์ยังไม่มีผล = ไม่ใช่เจตนาจริงของผู้ดูแล
 *    ⚠️ ขั้นนี้ย้อนกลับไม่ได้ — `down()` คืนค่าเดิมของสองคอลัมน์นี้ไม่ได้เพราะไม่ได้เก็บไว้
 * 3) drop คอลัมน์ที่ไม่มีใครอ่านแล้ว 3 ตัว
 * 4) ล้างแคช `academy_settings_{id}` ที่ `Academy::getSettings()` เก็บไว้ 24 ชม.
 *    ⚠️ ขาดขั้นนี้ไม่ได้ — migration เขียนผ่าน `DB::table()` ตรง ๆ hook `saved` ของโมเดลจึงไม่ทำงาน
 *    ผลคือหลัง deploy ระบบจะอ่านค่าเก่าจากแคช (พร้อมคอลัมน์ที่ถูก drop ไปแล้ว) ได้อีกนานถึง 24 ชม.
 *    ยืนยันของจริงมาแล้ว: ยิง API หลัง migrate ครั้งแรกได้ 403 ทั้งที่ค่าในฐานถูกรีเซ็ตเป็นเปิดแล้ว
 *
 * หมายเหตุ: `course_settings.auto_accept_members` เป็นคนละตาราง ไม่ถูกแตะในไฟล์นี้
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_settings')) {
            return;
        }

        if (Schema::hasColumn('academy_settings', 'auto_accept_members') && Schema::hasColumn('academy_settings', 'join_mode')) {
            // auto_accept = 1 แต่ join_mode ไม่ใช่ open ⇒ ให้เป็น open
            DB::table('academy_settings')
                ->where('auto_accept_members', 1)
                ->where(function ($query) {
                    $query->where('join_mode', '!=', 'open')->orWhereNull('join_mode');
                })
                ->update(['join_mode' => 'open']);

            // auto_accept = 0 แต่ join_mode เป็น open ⇒ ให้เป็น approval
            DB::table('academy_settings')
                ->where('auto_accept_members', 0)
                ->where('join_mode', 'open')
                ->update(['join_mode' => 'approval']);
        }

        if (Schema::hasColumn('academy_settings', 'join_mode')) {
            DB::table('academy_settings')
                ->where(function ($query) {
                    $query->whereNull('join_mode')
                        ->orWhereNotIn('join_mode', ['open', 'approval', 'invite_only']);
                })
                ->update(['join_mode' => 'approval']);
        }

        $reset = [];
        if (Schema::hasColumn('academy_settings', 'show_member_list')) {
            $reset['show_member_list'] = 1;
        }
        if (Schema::hasColumn('academy_settings', 'show_course_list')) {
            $reset['show_course_list'] = 1;
        }
        if ($reset !== []) {
            DB::table('academy_settings')->update($reset);
        }

        Schema::table('academy_settings', function (Blueprint $table) {
            foreach (['allow_student_registration', 'allow_parent_registration', 'auto_accept_members'] as $column) {
                if (Schema::hasColumn('academy_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->flushSettingsCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_settings')) {
            return;
        }

        Schema::table('academy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('academy_settings', 'auto_accept_members')) {
                $table->boolean('auto_accept_members')->default(false);
            }
            if (! Schema::hasColumn('academy_settings', 'allow_student_registration')) {
                $table->boolean('allow_student_registration')->default(true);
            }
            if (! Schema::hasColumn('academy_settings', 'allow_parent_registration')) {
                $table->boolean('allow_parent_registration')->default(true);
            }
        });

        // คืนค่า auto_accept_members จาก join_mode ให้ระบบเก่ายังทำงานได้
        if (Schema::hasColumn('academy_settings', 'join_mode')) {
            DB::table('academy_settings')->where('join_mode', 'open')->update(['auto_accept_members' => 1]);
            DB::table('academy_settings')->where('join_mode', '!=', 'open')->update(['auto_accept_members' => 0]);
        }

        $this->flushSettingsCache();
    }

    /**
     * `Academy::getSettings()` แคชโมเดล `AcademySetting` ไว้ 24 ชม. ด้วยคีย์ `academy_settings_{academy_id}`
     * migration นี้เขียนผ่าน `DB::table()` ⇒ event `saved` ไม่ทำงาน ⇒ ต้องล้างแคชเอง
     */
    private function flushSettingsCache(): void
    {
        foreach (DB::table('academy_settings')->pluck('academy_id') as $academyId) {
            Cache::forget("academy_settings_{$academyId}");
        }
    }
};
