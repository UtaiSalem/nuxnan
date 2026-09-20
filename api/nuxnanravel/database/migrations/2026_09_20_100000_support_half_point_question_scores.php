<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * คะแนนต่อข้อของข้อสอบต้องตั้งเป็น 0.5 ได้ แต่ทั้งสายเก็บเป็น int/bigint
 * ซึ่ง MySQL ปัดเศษให้เงียบ ๆ (0.5 -> 1) โดยไม่มี error
 * ตอนเขียน migration นี้ questions ทุกแถวมี points = 1 จึงแปลงชนิดได้โดยไม่เสียข้อมูล
 */
return new class extends Migration
{
    /**
     * [ตาราง, คอลัมน์, นิยามใหม่, นิยามเดิม]
     */
    private array $columns = [
        ['questions', 'points', 'DECIMAL(8,2) NOT NULL DEFAULT 1.00', 'INT NOT NULL'],
        ['user_answer_questions', 'points', 'DECIMAL(8,2) NOT NULL DEFAULT 0.00', 'BIGINT UNSIGNED NOT NULL'],
        ['lesson_answer_questions', 'points', 'DECIMAL(8,2) NOT NULL DEFAULT 0.00', 'BIGINT UNSIGNED NOT NULL DEFAULT 0'],
        ['course_quizzes', 'total_score', 'DECIMAL(10,2) NULL DEFAULT 0.00', 'INT NULL DEFAULT 0'],
        ['course_quiz_results', 'score', 'DECIMAL(8,2) NOT NULL DEFAULT 0.00', 'INT NOT NULL DEFAULT 0'],
        ['courses', 'total_score', 'DECIMAL(10,2) NULL DEFAULT 0.00', 'INT NULL DEFAULT 0'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach ($this->columns as [$table, $column, $new, $old]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$new}");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach ($this->columns as [$table, $column, $new, $old]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            // ย้อนกลับเป็นจำนวนเต็ม: ปัดค่าที่มีเศษก่อน ไม่งั้น STRICT mode จะตีกลับ
            DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`) WHERE `{$column}` IS NOT NULL");
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$old}");
        }
    }
};
