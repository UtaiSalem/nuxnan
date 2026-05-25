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
        Schema::create('lesson_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id');
            $table->enum('access_type', ['free', 'points', 'money', 'manual', 'admin'])
                  ->default('free');
            $table->unsignedBigInteger('points_transaction_id')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->decimal('amount', 10, 2)->unsigned()->default(0);
            $table->enum('status', ['active', 'refunded', 'revoked'])->default('active');
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamps();

            // กัน user จ่ายซ้ำ — unique key สำคัญที่สุด
            $table->unique(['user_id', 'lesson_id'], 'lesson_accesses_user_lesson_unique');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->index(['lesson_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_accesses');
    }
};
