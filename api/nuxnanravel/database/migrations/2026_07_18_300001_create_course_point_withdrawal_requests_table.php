<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_point_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_point_account_id')->constrained('course_point_accounts')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('purpose', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_proof_path')->nullable();
            $table->string('payout_proof_original_name')->nullable();
            $table->string('payout_proof_mime')->nullable();
            $table->unsignedInteger('payout_proof_size')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('course_point_transaction_id')->nullable();
            $table->foreign('course_point_transaction_id', 'cpwr_cpt_id_fk')
                ->references('id')->on('course_point_transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['course_id', 'status', 'created_at'], 'cpwr_course_status_ca_idx');
            $table->index(['requested_by', 'created_at'], 'cpwr_req_ca_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_point_withdrawal_requests');
    }
};
