<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_point_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('cycle_type', 16);
            $table->string('cycle_key', 32);
            $table->date('cycle_start');
            $table->date('cycle_end')->nullable();
            $table->unsignedBigInteger('total_points')->default(0);
            $table->timestamps();
            $table->unique(['classroom_group_id', 'cycle_type', 'cycle_key'], 'classroom_point_cycle_unique');
            $table->index(['academy_id', 'cycle_type', 'cycle_key']);
            $table->index(['total_points']); // for leaderboard ORDER BY DESC
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_point_cycles');
    }
};
