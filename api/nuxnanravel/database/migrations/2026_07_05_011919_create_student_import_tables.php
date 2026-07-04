<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained();
            $table->string('filename');
            $table->string('original_filename');
            $table->enum('status', [
                'uploaded',
                'validating',
                'validated',
                'processing',
                'completed',
                'partial',
                'failed',
                'cancelled',
            ]);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->json('column_mapping')->nullable();
            $table->json('default_values')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'status']);
        });

        Schema::create('student_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('student_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->enum('status', [
                'pending',
                'valid',
                'warning',
                'invalid',
                'imported',
                'skipped',
                'failed',
            ]);
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('student_id')->nullable()->constrained();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['batch_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_import_rows');
        Schema::dropIfExists('student_import_batches');
    }
};
