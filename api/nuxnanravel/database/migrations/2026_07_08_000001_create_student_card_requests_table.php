<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_card_requests')) {
            Schema::create('student_card_requests', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('existing_card_id')->nullable();
                $table->integer('result_card_id')->nullable();
                $table->string('request_type', 24);
                $table->string('status', 24)->default('pending');
                $table->string('priority', 16)->default('normal');
                $table->string('origin', 16)->default('teacher');
                $table->string('full_name_snapshot', 255);
                $table->string('student_number_snapshot', 64)->nullable();
                $table->string('grade_level_snapshot', 64)->nullable();
                $table->string('section_snapshot', 64)->nullable();
                $table->text('reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->boolean('is_open_flag')->nullable()->virtualAs("CASE WHEN status IN ('pending','approved','in_progress') THEN 1 ELSE NULL END");
                $table->timestamps();

                $table->foreign('existing_card_id')->references('id')->on('student_cards')->nullOnDelete();
                $table->foreign('result_card_id')->references('id')->on('student_cards')->nullOnDelete();
                $table->index(['academy_id', 'status']);
                $table->unique(['student_id', 'academy_id', 'is_open_flag'], 'uq_student_card_request_open');
            });
        }

        if (! Schema::hasColumn('academy_settings', 'card_request_flow_enabled')) {
            Schema::table('academy_settings', function (Blueprint $table) {
                $table->boolean('card_request_flow_enabled')->default(false)->after('auto_accept_members');
            });
        }
    }

    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn('card_request_flow_enabled');
        });
        Schema::dropIfExists('student_card_requests');
    }
};
