<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'min_sessions_for_eligibility_check')) {
                $table->unsignedInteger('min_sessions_for_eligibility_check')->default(3)
                    ->after('max_absence_percent')
                    ->comment('จำนวนคาบเรียนขั้นต่ำก่อนคำนวณสิทธิ์สอบ');
            }
            if (!Schema::hasColumn('courses', 'allow_unlock_by_appeal')) {
                $table->boolean('allow_unlock_by_appeal')->default(true)
                    ->after('allow_unlock_by_reading');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['min_sessions_for_eligibility_check', 'allow_unlock_by_appeal']);
        });
    }
};
