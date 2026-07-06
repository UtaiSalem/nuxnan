<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม academy_id ในตารางที่เกี่ยวข้องกับนักเรียนทั้งหมด
 * เพื่อรองรับระบบหลายโรงเรียน (multi-academy)
 * ทำให้สามารถแยกข้อมูลนักเรียนตามโรงเรียนได้อย่างชัดเจน
 */
return new class extends Migration
{
    /**
     * ตารางที่มี student_id เป็น FK อยู่แล้ว
     */
    private array $tablesWithStudentId = [
        'student_academic_info',
        'student_addresses',
        'student_contacts',
        'student_guardians',
        'student_health_info',
        'student_health_records',
        'student_documents',
        'student_home_visits',
        'classroom_students',
        'gradebook_scores',
        'course_grades',
        'curriculum_students',
    ];

    /**
     * ตาราง legacy ที่ไม่มี student_id FK
     */
    private array $legacyTables = [
        'student_cards',
    ];

    public function up(): void
    {
        // เพิ่ม academy_id ในตาราง students (ถ้ายังไม่มี)
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'academy_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('id');
                $table->unsignedBigInteger('user_id')->nullable()->after('academy_id');
                $table->index('academy_id');
                $table->index('user_id');
            });
        }

        // เพิ่ม academy_id ในตารางที่มี student_id
        foreach ($this->tablesWithStudentId as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'academy_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('academy_id')->nullable()->after('id');
                    $table->index('academy_id');
                });
            }
        }

        // เพิ่ม academy_id ในตาราง legacy
        foreach ($this->legacyTables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'academy_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('academy_id')->nullable()->after('id');
                    $table->index('academy_id');
                });
            }
        }

        // Backfill: อัปเดต academy_id จากตาราง students สำหรับตารางที่มี student_id
        // (เฉพาะกรณีที่ students มี academy_id อยู่แล้ว)
        if (Schema::hasColumn('students', 'academy_id')) {
            foreach ($this->tablesWithStudentId as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'academy_id') && Schema::hasColumn($tableName, 'student_id')) {
                    if (DB::getDriverName() === 'mysql') {
                        DB::statement("
                            UPDATE `{$tableName}` t
                            INNER JOIN `students` s ON t.student_id = s.id
                            SET t.academy_id = s.academy_id
                            WHERE t.academy_id IS NULL AND s.academy_id IS NOT NULL
                        ");
                    } elseif (DB::getDriverName() === 'sqlite') {
                        DB::statement("
                            UPDATE `{$tableName}` 
                            SET academy_id = (SELECT academy_id FROM students WHERE students.id = `{$tableName}`.student_id)
                            WHERE academy_id IS NULL 
                            AND EXISTS (SELECT 1 FROM students WHERE students.id = `{$tableName}`.student_id AND academy_id IS NOT NULL)
                        ");
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $allTables = array_merge($this->tablesWithStudentId, $this->legacyTables);

        foreach ($allTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'academy_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['academy_id']);
                    $table->dropColumn('academy_id');
                });
            }
        }

        // ลบ academy_id และ user_id จากตาราง students (ถ้าเพิ่มไว้ใน migration นี้)
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'academy_id')) {
                    $table->dropIndex(['academy_id']);
                    $table->dropColumn('academy_id');
                }
                if (Schema::hasColumn('students', 'user_id')) {
                    $table->dropIndex(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }
    }
};
