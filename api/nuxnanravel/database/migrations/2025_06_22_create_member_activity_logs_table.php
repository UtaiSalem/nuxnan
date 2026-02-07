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
        Schema::create('member_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who performed the action');
            $table->unsignedBigInteger('target_user_id')->nullable()->comment('User who was affected');
            $table->unsignedBigInteger('academy_member_id')->nullable()->comment('Related member record');
            $table->string('action', 50)->comment('Action type: join, leave, approve, reject, suspend, role_change, etc.');
            $table->string('action_category', 30)->default('member')->comment('Category: member, role, course, attendance, etc.');
            $table->json('old_values')->nullable()->comment('Previous values before change');
            $table->json('new_values')->nullable()->comment('New values after change');
            $table->text('description')->nullable()->comment('Human-readable description');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('academy_member_id')->references('id')->on('academy_members')->onDelete('set null');

            $table->index(['academy_id', 'created_at']);
            $table->index(['academy_id', 'action']);
            $table->index(['academy_id', 'user_id']);
            $table->index(['academy_id', 'target_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_activity_logs');
    }
};
