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
        Schema::create('course_point_campaign_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('points_amount');
            $table->unsignedBigInteger('points_transaction_id');          // FK → points_transactions (ฝั่ง user)
            $table->unsignedBigInteger('course_point_transaction_id');    // FK → course_point_transactions (ฝั่ง course)
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            // 🔐 กัน claim ซ้ำ ระดับ DB constraint
            $table->unique(['campaign_id', 'user_id'], 'campaign_claims_unique');

            $table->foreign('campaign_id')->references('id')->on('course_point_campaigns')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_point_campaign_claims');
    }
};
