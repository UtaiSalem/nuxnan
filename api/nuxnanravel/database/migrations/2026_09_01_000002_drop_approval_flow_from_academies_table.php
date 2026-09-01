<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SET-S7 / D18: drop approval_flow - dead column ไม่มีผู้อ่านทั้ง app/ และ ui/
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('approval_flow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->string('approval_flow', 191)->default('single')
                ->comment('single | two_level')
                ->after('student_editable_fields');
        });
    }
};
