<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_point_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->unique()->constrained('academies')->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('reserved_balance')->default(0);
            $table->unsignedBigInteger('platform_earned')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->unsignedBigInteger('total_withdrawn')->default(0);
            $table->unsignedBigInteger('total_distributed')->default(0);
            $table->unsignedBigInteger('minimum_withdrawal')->default(24000);
            $table->decimal('commission_rate', 6, 4)->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_point_accounts');
    }
};
