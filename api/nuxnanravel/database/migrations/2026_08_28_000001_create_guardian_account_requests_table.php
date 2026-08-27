<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imported production dumps already carry this table while the migrations
        // table does not record it. Guard so `migrate` stays runnable.
        if (Schema::hasTable('guardian_account_requests')) {
            return;
        }

        Schema::create('guardian_account_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_id')->index();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('direction', 20);
            $table->unsignedBigInteger('initiated_by_user_id')->nullable();
            $table->string('initiated_by_role', 30)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('responded_by_user_id')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('decline_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['academy_id', 'status']);

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_account_requests');
    }
};
