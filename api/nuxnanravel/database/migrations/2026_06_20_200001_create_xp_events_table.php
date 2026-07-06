<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who triggered
            $table->foreignId('classroom_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->string('source', 64); // 'post.created' / 'attendance.recorded' / ...
            $table->integer('xp');         // school XP awarded (0 if classroom-only)
            $table->integer('classroom_pts')->default(0); // classroom pts (0 if school-only)
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['academy_id', 'occurred_at']);
            $table->index(['classroom_group_id', 'occurred_at']);
            $table->index(['source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
