<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('school_xp_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('cycle_type', 16); // 'week' | 'month' | 'all_time'
            $table->string('cycle_key', 32);  // '2026-W26', '2026-06', 'all'
            $table->date('cycle_start');
            $table->date('cycle_end')->nullable();
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();
            $table->unique(['academy_id', 'cycle_type', 'cycle_key'], 'school_xp_cycle_unique');
            $table->index(['cycle_type', 'cycle_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('school_xp_cycles'); }
};
