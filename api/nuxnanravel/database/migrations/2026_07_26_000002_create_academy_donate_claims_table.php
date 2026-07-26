<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_donate_claims')) {
            return;
        }
        Schema::create('academy_donate_claims', function (Blueprint $table) {
            $table->id();
            // The legacy academy_donates table is not FK-compatible in some deployed schemas.
            $table->unsignedBigInteger('academy_donate_id');
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('claimer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('suggester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('amount_claimer')->default(210);
            $table->unsignedInteger('amount_suggester')->default(30);
            $table->unsignedInteger('amount_school')->default(20);
            $table->unsignedInteger('amount_platform')->default(10);
            $table->foreignId('claimer_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
            $table->foreignId('suggester_transaction_id')->nullable()->constrained('points_transactions')->nullOnDelete();
            $table->foreignId('school_transaction_id')->constrained('academy_point_transactions')->cascadeOnDelete();
            $table->foreignId('platform_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamps();
            $table->index(['academy_donate_id', 'claimer_id', 'claimed_at']);
            $table->index(['claimer_id', 'claimed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_donate_claims');
    }
};
