<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อม schema drift ที่ติดมากับ DB dump ที่ import เมื่อ 2026-08-01
 *
 * ตาราง `migrations` ระบุว่า migration เหล่านี้รันไปแล้ว (batch 47 รวด 227 ตัว)
 * แต่คอลัมน์ที่มันเพิ่มไม่มีอยู่จริงใน DB — `php artisan migrate` จึงข้ามตลอด
 * migration นี้เติมเฉพาะคอลัมน์ที่มีโค้ดใช้งานจริงกลับเข้าไป
 *
 * ทุกคำสั่งห่อด้วย hasColumn() → รันซ้ำได้ และรันบน DB ที่ปกติอยู่แล้วก็ไม่มีผลอะไร
 */
return new class extends Migration
{
    public function up(): void
    {
        // 2026_07_23_180000_add_donor_view_to_course_claims
        if (! Schema::hasColumn('course_donates', 'remaining_points')) {
            Schema::table('course_donates', function (Blueprint $table) {
                $table->unsignedBigInteger('remaining_points')->nullable()->default(0)->after('points_amount');
            });

            DB::table('course_donates')
                ->where('donation_type', 'point')
                ->whereIn('status', ['approved', 'completed'])
                ->update(['remaining_points' => DB::raw('points_amount')]);
        }

        // 2026_01_13_150811_add_points_columns_to_polls_table
        Schema::table('polls', function (Blueprint $table) {
            if (! Schema::hasColumn('polls', 'points_pool')) {
                $table->integer('points_pool')->default(0)->after('image_url');
            }
            if (! Schema::hasColumn('polls', 'points_per_vote')) {
                $table->integer('points_per_vote')->default(0)->after('points_pool');
            }
            if (! Schema::hasColumn('polls', 'points_distributed')) {
                $table->integer('points_distributed')->default(0)->after('points_per_vote');
            }
        });

        // 2025_11_14_100000_add_logo_and_headers_to_courses_table
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'logo')) {
                $table->string('logo')->nullable()->after('cover');
            }
            if (! Schema::hasColumn('courses', 'cover_header')) {
                $table->string('cover_header')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('courses', 'cover_subheader')) {
                $table->text('cover_subheader')->nullable()->after('cover_header');
            }
        });

        // 2026_02_01_010000_add_columns_to_roles_table
        $rolesNeedBackfill = ! Schema::hasColumn('roles', 'display_name');
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('roles', 'status')) {
                $table->boolean('status')->default(true)->after('description');
            }
        });

        if ($rolesNeedBackfill) {
            DB::table('roles')->whereNull('display_name')->update(['display_name' => DB::raw('name')]);
        }

        // 2026_02_05_011941_create_gamification_tables — `points` คือคอลัมน์ที่มีจริง
        // ส่วน `xp_reward` เป็นชื่อที่ Badge::$fillable กับ UserProfileResource ใช้
        if (! Schema::hasColumn('badges', 'xp_reward')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->integer('xp_reward')->default(0)->after('icon');
            });

            if (Schema::hasColumn('badges', 'points')) {
                DB::table('badges')->where('points', '>', 0)->update(['xp_reward' => DB::raw('points')]);
            }
        }

        // 2025_11_27_203000_create_user_profiles_table — ชื่อเดิมฝั่ง nuxni
        // ตอนนี้ `cover_image` ทำงานแทนอยู่ เติมไว้กัน mass-assign ล้ม
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'cover_image_url')) {
                $table->string('cover_image_url')->nullable()->after('cover_image');
            }
        });
    }

    /**
     * ตั้งใจให้เป็น no-op
     *
     * คอลัมน์ทุกตัวข้างบนเป็นของ migration ตัวอื่น และ down() ของ migration
     * เหล่านั้น drop มันอยู่แล้ว ถ้า down() ตัวนี้ drop ซ้ำ การ rollback
     * จะพังตอนวิ่งไปถึง migration เจ้าของจริง
     */
    public function down(): void
    {
        //
    }
};
