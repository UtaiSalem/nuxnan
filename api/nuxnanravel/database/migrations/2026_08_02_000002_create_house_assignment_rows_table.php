<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_assignment_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('row_number');
            $table->json('raw')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->enum('status', ['ok', 'unmatched', 'ambiguous', 'unknown_house', 'already_assigned', 'skipped']);
            $table->text('message')->nullable();
            $table->timestamps();
            $table->foreign('batch_id')->references('id')->on('house_assignment_batches')->cascadeOnDelete();
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_assignment_rows');
    }
};
