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
        Schema::create('wallet_deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('bank_transfer'); // bank_transfer, promptpay, credit_card
            $table->string('bank_name')->nullable(); // ชื่อธนาคาร
            $table->string('account_number')->nullable(); // เลขบัญชีที่โอน
            $table->string('account_name')->nullable(); // ชื่อบัญชีผู้โอน
            $table->string('transfer_slip')->nullable(); // path to slip image
            $table->timestamp('transfer_date')->nullable(); // วันที่โอน
            $table->string('transfer_time')->nullable(); // เวลาที่โอน
            $table->string('reference_number')->nullable(); // เลขอ้างอิงการโอน
            $table->text('note')->nullable(); // หมายเหตุจากผู้ใช้
            
            // Status: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable(); // หมายเหตุจาก Admin
            $table->string('rejection_reason')->nullable(); // เหตุผลที่ปฏิเสธ
            
            // Transaction reference (when approved)
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_deposit_requests');
    }
};
