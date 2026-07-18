<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_share_policies', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 16);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('student_pct', 5, 2);
            $table->decimal('course_pct', 5, 2);
            $table->decimal('platform_pct', 5, 2);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['scope_type', 'scope_id', 'effective_from']);
            $table->index(['scope_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_share_policies');
    }
};
