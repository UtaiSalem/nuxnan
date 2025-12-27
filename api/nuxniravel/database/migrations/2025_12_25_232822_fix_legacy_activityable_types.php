<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * แก้ไข activityable_type ที่ใช้ namespace เก่าให้เป็น namespace ปัจจุบัน
     * 
     * Legacy namespaces ที่ต้องแก้ไข:
     * - App\Models\Play\Post -> App\Models\Post
     * - App\Models\Learn\Academy\AcademyPost -> App\Models\AcademyPost
     * - App\Models\Learn\Course\Post\CoursePost -> App\Models\CoursePost
     * - App\Models\Earn\Donate -> App\Models\Donate
     * - App\Models\Earn\Support -> App\Models\Support
     * - App\Models\Earn\DonateRecipient -> App\Models\DonateRecipient
     * - App\Models\Play\Poll -> App\Models\Poll
     */
    public function up(): void
    {
        $mappings = [
            'App\\Models\\Play\\Post' => 'App\\Models\\Post',
            'App\\Models\\Learn\\Academy\\AcademyPost' => 'App\\Models\\AcademyPost',
            'App\\Models\\Learn\\Course\\Post\\CoursePost' => 'App\\Models\\CoursePost',
            'App\\Models\\Earn\\Donate' => 'App\\Models\\Donate',
            'App\\Models\\Earn\\Support' => 'App\\Models\\Support',
            'App\\Models\\Earn\\DonateRecipient' => 'App\\Models\\DonateRecipient',
            'App\\Models\\Play\\Poll' => 'App\\Models\\Poll',
        ];

        foreach ($mappings as $oldType => $newType) {
            DB::table('activities')
                ->where('activityable_type', $oldType)
                ->update(['activityable_type' => $newType]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ไม่ต้อง rollback เพราะ namespace ใหม่ถูกต้องกว่า
    }
};
