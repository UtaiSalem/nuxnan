<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->timestamp('retake_unlocked_at')->nullable()->after('status');
            $table->timestamp('retake_used_at')->nullable()->after('retake_unlocked_at');
            $table->unsignedBigInteger('retake_granted_by_enrollment_id')->nullable()->after('retake_used_at');
            
            $table->foreign('retake_granted_by_enrollment_id', 'cqr_retake_enrollment_fk')
                  ->references('id')->on('course_remediation_enrollments')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->dropForeign('cqr_retake_enrollment_fk');
            $table->dropColumn(['retake_unlocked_at', 'retake_used_at', 'retake_granted_by_enrollment_id']);
        });
    }
};
