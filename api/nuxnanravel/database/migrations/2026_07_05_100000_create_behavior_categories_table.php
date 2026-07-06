<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['positive', 'negative']);
            $table->unsignedInteger('default_points')->default(1);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['academy_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_categories');
    }
};
