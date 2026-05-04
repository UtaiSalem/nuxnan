<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Refactor Classroom Enrollment System
 *
 * เปลี่ยนจากระบบ direct field matching (students.class_level + class_section)
 * ไปใช้ classroom_students pivot table เป็น source of truth
 *
 * สาเหตุ:
 * 1. ไม่สามารถเก็บประวัติย้อนหลังได้ (ข้อมูลถูกเขียนทับเมื่อขึ้นปีใหม่)
 * 2. นักเรียน 1 คนควรอยู่ได้หลายห้องข้ามปีการศึกษา
 * 3. รองรับ Status เช่น ย้ายออกกลางคัน, พักการเรียน
 *
 * Changes:
 * 1. เพิ่ม academic_year_id ใน classroom_students ← สำคัญเพื่อ query ตามปีการศึกษา
 * 2. เพิ่ม classroom_id ใน student_academic_info ← เชื่อมข้อมูลวิชาการกับห้องเรียน
 * 3. Populate classroom_students จากข้อมูล class_level + class_section ที่มีอยู่
 * 4. ไม่ลบ class_level/class_section จาก students (backward compatible)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. เพิ่ม academy_id + academic_year_id ใน classroom_students
        // ──────────────────────────────────────────────
        if (!Schema::hasColumn('classroom_students', 'academy_id')) {
            Schema::table('classroom_students', function (Blueprint $table) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('id');
            });

            Schema::table('classroom_students', function (Blueprint $table) {
                $table->foreign('academy_id', 'fk_cs_academy_id')
                      ->references('id')->on('academies')
                      ->onDelete('cascade');
                $table->index('academy_id', 'cs_academy_idx');
            });
        }

        if (!Schema::hasColumn('classroom_students', 'academic_year_id')) {
            Schema::table('classroom_students', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('academy_id');
            });

            Schema::table('classroom_students', function (Blueprint $table) {
                $table->foreign('academic_year_id', 'fk_cs_academic_year_id')
                      ->references('id')->on('academic_years')
                      ->onDelete('set null');
                $table->index(['academic_year_id', 'status'], 'cs_year_status_idx');
            });
        }

        // ──────────────────────────────────────────────
        // 2. เพิ่ม academy_id + classroom_id ใน student_academic_info
        // ──────────────────────────────────────────────
        if (!Schema::hasColumn('student_academic_info', 'academy_id')) {
            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('student_id');
            });

            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->foreign('academy_id', 'fk_sai_academy_id')
                      ->references('id')->on('academies')
                      ->onDelete('set null');
                $table->index('academy_id', 'sai_academy_idx');
            });

            // Populate academy_id from student's academy
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    UPDATE student_academic_info sai
                    INNER JOIN students s ON s.id = sai.student_id
                    SET sai.academy_id = s.academy_id
                    WHERE sai.academy_id IS NULL AND s.academy_id IS NOT NULL
                ");
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement("
                    UPDATE student_academic_info 
                    SET academy_id = (SELECT academy_id FROM students WHERE students.id = student_academic_info.student_id)
                    WHERE academy_id IS NULL 
                    AND EXISTS (SELECT 1 FROM students WHERE students.id = student_academic_info.student_id AND academy_id IS NOT NULL)
                ");
            }
        }

        if (!Schema::hasColumn('student_academic_info', 'classroom_id')) {
            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->unsignedBigInteger('classroom_id')->nullable()->after('academy_id');
            });

            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->foreign('classroom_id', 'fk_sai_classroom_id')
                      ->references('id')->on('classrooms')
                      ->onDelete('set null');
                $table->index('classroom_id', 'sai_classroom_idx');
            });
        }

        // ──────────────────────────────────────────────
        // 3. เพิ่ม enrolled_at ใน classroom_students (วันที่เข้าห้อง)
        // ──────────────────────────────────────────────
        if (!Schema::hasColumn('classroom_students', 'enrolled_at')) {
            Schema::table('classroom_students', function (Blueprint $table) {
                $table->date('enrolled_at')->nullable()->after('status')
                      ->comment('วันที่เข้าห้องเรียน');
                $table->date('left_at')->nullable()->after('enrolled_at')
                      ->comment('วันที่ออกจากห้อง (ย้าย/จบ)');
                $table->string('leave_reason', 100)->nullable()->after('left_at')
                      ->comment('เหตุผลที่ออก');
            });
        }

        // ──────────────────────────────────────────────
        // 4. Populate classroom_students จากข้อมูลที่มีอยู่
        //    Match: students.class_level + class_section → classrooms.grade_level + section
        //    ภายใน academy เดียวกัน
        // ──────────────────────────────────────────────
        $this->populateClassroomStudents();

        // ──────────────────────────────────────────────
        // 5. Populate classroom_id ใน student_academic_info
        // ──────────────────────────────────────────────
        $this->populateAcademicInfoClassroomId();
    }

    /**
     * Populate classroom_students from students.class_level + class_section
     */
    private function populateClassroomStudents(): void
    {
        // Only populate if the table is mostly empty
        $existingCount = DB::table('classroom_students')->count();
        if ($existingCount > 100) {
            // Already populated, skip
            return;
        }

        // Insert into classroom_students by matching grade_level+section+academy
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                INSERT IGNORE INTO classroom_students 
                    (academy_id, classroom_id, student_id, academic_year_id, student_number, status, created_at, updated_at)
                SELECT 
                    s.academy_id,
                    c.id as classroom_id,
                    s.id as student_id,
                    c.academic_year_id,
                    NULL as student_number,
                    CASE 
                        WHEN s.status = 'active' THEN 'active'
                        WHEN s.status = 'graduated' THEN 'graduated'
                        WHEN s.status = 'transferred' THEN 'transferred'
                        ELSE 'active'
                    END as status,
                    NOW(),
                    NOW()
                FROM students s
                INNER JOIN classrooms c 
                    ON c.academy_id = s.academy_id
                    AND c.grade_level = s.class_level
                    AND c.section = s.class_section
                WHERE s.class_level IS NOT NULL 
                    AND s.class_level != ''
                    AND s.class_section IS NOT NULL
                    AND s.class_section != ''
                    AND s.academy_id IS NOT NULL
            ");
        } elseif (DB::getDriverName() === 'sqlite') {
            // Basic sqlite implementation for tests
            DB::statement("
                INSERT OR IGNORE INTO classroom_students 
                    (academy_id, classroom_id, student_id, academic_year_id, student_number, status, created_at, updated_at)
                SELECT 
                    s.academy_id,
                    c.id as classroom_id,
                    s.id as student_id,
                    c.academic_year_id,
                    NULL as student_number,
                    'active' as status,
                    datetime('now'),
                    datetime('now')
                FROM students s
                JOIN classrooms c ON c.academy_id = s.academy_id AND c.grade_level = s.class_level AND c.section = s.class_section
            ");
        }
    }

    /**
     * Populate classroom_id in student_academic_info
     */
    private function populateAcademicInfoClassroomId(): void
    {
        // Update student_academic_info with classroom_id from classroom_students
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE student_academic_info sai
                INNER JOIN classroom_students cs ON cs.student_id = sai.student_id AND cs.status = 'active'
                SET sai.classroom_id = cs.classroom_id
                WHERE sai.classroom_id IS NULL
                    AND sai.is_current = 1
            ");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE student_academic_info 
                SET classroom_id = (
                    SELECT classroom_id 
                    FROM classroom_students 
                    WHERE classroom_students.student_id = student_academic_info.student_id AND status = 'active'
                    LIMIT 1
                )
                WHERE classroom_id IS NULL AND is_current = 1
            ");
        }
    }

    public function down(): void
    {
        // Remove FKs and columns from student_academic_info
        Schema::table('student_academic_info', function (Blueprint $table) {
            if (Schema::hasColumn('student_academic_info', 'classroom_id')) {
                $table->dropForeign('fk_sai_classroom_id');
                $table->dropIndex('sai_classroom_idx');
                $table->dropColumn('classroom_id');
            }
            if (Schema::hasColumn('student_academic_info', 'academy_id')) {
                $table->dropForeign('fk_sai_academy_id');
                $table->dropIndex('sai_academy_idx');
                $table->dropColumn('academy_id');
            }
        });

        // Remove FKs and columns from classroom_students
        Schema::table('classroom_students', function (Blueprint $table) {
            if (Schema::hasColumn('classroom_students', 'academic_year_id')) {
                $table->dropForeign('fk_cs_academic_year_id');
                $table->dropIndex('cs_year_status_idx');
                $table->dropColumn('academic_year_id');
            }
            if (Schema::hasColumn('classroom_students', 'academy_id')) {
                $table->dropForeign('fk_cs_academy_id');
                $table->dropIndex('cs_academy_idx');
                $table->dropColumn('academy_id');
            }
            if (Schema::hasColumn('classroom_students', 'enrolled_at')) {
                $table->dropColumn(['enrolled_at', 'left_at', 'leave_reason']);
            }
        });
    }
};
