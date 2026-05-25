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
        Schema::create('course_point_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_point_account_id');
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('points_per_claim');         // แต้มที่นักเรียนแต่ละคนได้
            $table->unsignedInteger('max_claims')->nullable();       // null = ไม่จำกัดจำนวนคน
            $table->unsignedInteger('total_claimed')->default(0);    // กี่คนที่ claim แล้ว
            $table->unsignedBigInteger('total_points_claimed')->default(0); // แต้มรวมที่ถูกแจก
            // eligibility: all_enrolled เท่านั้นก่อน
            $table->enum('eligible_type', ['all_enrolled'])->default('all_enrolled');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['active', 'paused', 'ended', 'depleted'])->default('active');
            $table->unsignedBigInteger('created_by'); // owner หรือ admin ที่สร้าง
            $table->timestamps();

            $table->foreign('course_point_account_id')->references('id')->on('course_point_accounts')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_point_campaigns');
    }
};
