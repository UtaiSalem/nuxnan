<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('house_assignment_batches')) {
            return;
        }
        Schema::create('house_assignment_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['random', 'import']);
            $table->enum('status', ['draft', 'committed', 'undone'])->default('draft');
            $table->json('options');
            $table->json('summary')->nullable();
            $table->string('source_filename')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamp('committed_at')->nullable();
            $table->foreignId('committed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['academy_id', 'academic_year_id', 'status'], 'hab_academy_year_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_assignment_batches');
    }
};
