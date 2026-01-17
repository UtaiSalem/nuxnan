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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('coupon_code', 32)->unique();
            $table->enum('coupon_type', ['points', 'wallet']);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled'])->default('active');
            $table->text('description')->nullable();
            $table->string('qr_code_path', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('coupon_code');
            $table->index('user_id');
            $table->index('status');
            $table->index('coupon_type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
