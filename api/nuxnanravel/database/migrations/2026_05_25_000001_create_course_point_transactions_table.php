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
        Schema::create('course_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_point_account_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable();   // กรณี lesson_income
            $table->unsignedBigInteger('user_id')->nullable();     // นักเรียนที่จ่าย / owner ที่ถอน / ผู้รับ claim
            $table->enum('type', [
                'lesson_income',      // รับจากการ unlock
                'owner_withdraw',     // owner ถอน
                'campaign_debit',     // ตัดเพื่อ campaign
                'student_claim',      // นักเรียนกดรับ
                'refund',             // คืนเงินกรณี lesson refund
            ]);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->unsignedBigInteger('related_points_transaction_id')->nullable(); // FK → points_transactions
            $table->unsignedBigInteger('related_campaign_id')->nullable();           // FK → course_point_campaigns
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user_id ที่ trigger action
            $table->timestamps();

            $table->foreign('course_point_account_id')->references('id')->on('course_point_accounts')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('lesson_id')->references('id')->on('lessons')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['course_id', 'type', 'created_at']); // query ประวัติรายวิชา
            $table->index(['user_id', 'type']);                  // query ประวัติ per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_point_transactions');
    }
};
