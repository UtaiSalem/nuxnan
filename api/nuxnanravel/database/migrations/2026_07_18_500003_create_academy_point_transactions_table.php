<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_point_account_id')->constrained('academy_point_accounts')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->unsignedBigInteger('related_points_transaction_id')->nullable();
            $table->unsignedBigInteger('related_academy_donate_id')->nullable();
            $table->unsignedBigInteger('related_course_point_transaction_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['academy_id', 'type', 'created_at'], 'apt_academy_type_ca_idx');
            $table->index(['user_id', 'type'], 'apt_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_point_transactions');
    }
};
