<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_sessions');
    }
};
