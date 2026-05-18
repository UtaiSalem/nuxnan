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
        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_course_id');
            $table->unsignedBigInteger('cloned_course_id')->nullable();
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('seller_id');
            
            $table->decimal('amount_wallet', 12, 2)->default(0);
            $table->integer('amount_points')->default(0);
            
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->unsignedBigInteger('points_transaction_id')->nullable();
            $table->unsignedBigInteger('seller_income_transaction_id')->nullable();
            
            $table->string('payment_mode', 20); // wallet, points, mixed, free
            $table->string('status', 30)->default('pending_payment'); // pending_payment, paid, pending_clone, completed, failed, refunded
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cloned_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['source_course_id', 'buyer_id']);
            $table->index('status');
            
            // Foreign keys (optional depending on system preference, but good for integrity)
            $table->foreign('source_course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_purchases');
    }
};
