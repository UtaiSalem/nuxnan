<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_eligibility_overrides', function (Blueprint $table) {
            $table->text('appeal_reason')->nullable()->after('admin_reason');
            $table->json('appeal_evidence')->nullable()->after('appeal_reason');
            $table->foreignId('remediation_session_id')->nullable()
                ->after('appeal_evidence')
                ->constrained('course_remediation_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_eligibility_overrides', function (Blueprint $table) {
            $table->dropForeign(['remediation_session_id']);
            $table->dropColumn(['appeal_reason', 'appeal_evidence', 'remediation_session_id']);
        });
    }
};
