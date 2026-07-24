<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_point_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('academy_point_account_id')->constrained('academy_point_accounts')->cascadeOnDelete();
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
            $table->string('payment_reference')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('academy_point_transaction_id')->nullable();
            $table->foreign('academy_point_transaction_id', 'apwr_apt_id_fk')
                ->references('id')->on('academy_point_transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['academy_id', 'status', 'created_at'], 'apwr_academy_status_ca_idx');
            $table->index(['requested_by', 'created_at'], 'apwr_req_ca_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_point_withdrawal_requests');
    }
};
