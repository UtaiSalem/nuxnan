<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_events', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name', 64);
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id');
            $table->string('severity', 16);
            $table->unsignedInteger('score');
            $table->json('evidence');
            $table->string('status', 16)->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->string('deduplication_key', 128)->nullable()->unique();
            $table->timestamps();
            $table->index(['rule_name', 'status', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_events');
    }
};
