<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->string('change_type', 30)
                ->comment('auto_calc, points_unlock, reading_unlock, admin_unlock, appeal_unlock, remediation_unlock, admin_revoke, expiration');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['course_member_id', 'created_at']);
            $table->index(['course_id', 'change_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_audit_logs');
    }
};
