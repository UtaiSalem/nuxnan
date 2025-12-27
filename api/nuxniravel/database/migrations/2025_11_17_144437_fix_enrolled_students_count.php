<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * แก้ไขจำนวนสมาชิกที่นับซ้ำซ้อนให้ถูกต้อง
     * นับจาก course_members ที่มี course_member_status = 1 
     * และมี record ใน course_group_members (ไม่ต้องเช็ค status เพราะเป็น '0' ทั้งหมด)
     * และ role in (1, 2) = นักเรียนหรือหัวหน้ากลุ่ม
     */
    public function up(): void
    {
        // Update enrolled_students count for all courses
        // นับจาก course_members ที่ JOIN กับ course_group_members
        DB::statement("
            UPDATE courses
            SET enrolled_students = (
                SELECT COUNT(DISTINCT cm.id)
                FROM course_members cm
                INNER JOIN course_group_members cgm 
                    ON cgm.course_id = cm.course_id 
                    AND cgm.user_id = cm.user_id
                WHERE cm.course_id = courses.id
                AND (cm.course_member_status = 1 OR cm.status = 1)
                AND cm.role IN (1, 2)
            )
        ");
        
        // Log the results
        $totalCourses = DB::table('courses')->count();
        $updatedCourses = DB::table('courses')->where('enrolled_students', '>', 0)->count();
        $totalMembers = DB::table('course_members as cm')
            ->join('course_group_members as cgm', function($join) {
                $join->on('cm.course_id', '=', 'cgm.course_id')
                     ->on('cm.user_id', '=', 'cgm.user_id');
            })
            ->where(function($query) {
                $query->where('cm.course_member_status', 1)
                      ->orWhere('cm.status', 1);
            })
            ->whereIn('cm.role', [1, 2])
            ->count();
        
        echo "\nFixed enrolled_students count (from cross table):\n";
        echo "Total courses: {$totalCourses}\n";
        echo "Courses with members: {$updatedCourses}\n";
        echo "Total enrolled members: {$totalMembers}\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't know the original incorrect values
        echo "Cannot reverse this migration - enrolled_students counts have been corrected.\n";
    }
};
