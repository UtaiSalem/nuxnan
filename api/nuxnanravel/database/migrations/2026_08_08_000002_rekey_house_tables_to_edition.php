<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['house_memberships', 'house_assignment_rows', 'house_assignment_batches'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->count() > 0) {
                throw new RuntimeException(
                    "S-S3e cannot rekey {$table}: it already holds rows. ".
                    'Migrate the existing data deliberately instead of dropping it.'
                );
            }
        }

        Schema::dropIfExists('house_assignment_rows');
        Schema::dropIfExists('house_memberships');
        Schema::dropIfExists('house_assignment_batches');

        Schema::create('house_assignment_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
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
            $table->index(['edition_id', 'status'], 'hab_edition_status_idx');
        });

        Schema::create('house_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['random', 'import', 'manual']);
            $table->uuid('batch_id')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['edition_id', 'student_id'], 'hm_edition_student_unique');
            $table->index(['edition_id', 'house_group_id'], 'hm_edition_house_idx');
            $table->foreign('batch_id')->references('id')->on('house_assignment_batches')->nullOnDelete();
        });

        Schema::create('house_assignment_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('row_number');
            $table->json('raw')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->foreignId('previous_house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
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
        Schema::dropIfExists('house_memberships');
        Schema::dropIfExists('house_assignment_batches');

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

        Schema::create('house_assignment_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('row_number');
            $table->json('raw')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->foreignId('previous_house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->enum('status', ['ok', 'unmatched', 'ambiguous', 'unknown_house', 'already_assigned', 'skipped']);
            $table->text('message')->nullable();
            $table->timestamps();
            $table->foreign('batch_id')->references('id')->on('house_assignment_batches')->cascadeOnDelete();
            $table->index(['batch_id', 'status']);
        });
    }
};
