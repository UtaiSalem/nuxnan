<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_member_grade_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('earned_score', 10, 2);
            $table->decimal('max_score', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();

            $table->json('breakdown_json')->nullable();
            $table->uuid('published_run_id')->index();
            $table->boolean('is_current')->default(true)->index();

            $table->timestamp('published_at')->useCurrent();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['course_member_id', 'published_run_id'], 'unique_member_run');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_member_grade_snapshots');
    }
};
