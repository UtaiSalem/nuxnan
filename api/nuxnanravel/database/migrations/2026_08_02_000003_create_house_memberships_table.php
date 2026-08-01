<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('house_memberships')) {
            return;
        }
        Schema::create('house_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['random', 'import', 'manual']);
            $table->uuid('batch_id')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['academic_year_id', 'student_id']);
            $table->index(['academy_id', 'academic_year_id', 'house_group_id'], 'hm_academy_year_house_idx');
            $table->foreign('batch_id')->references('id')->on('house_assignment_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_memberships');
    }
};
