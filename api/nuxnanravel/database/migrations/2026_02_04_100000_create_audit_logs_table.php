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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index(); // e.g., 'created', 'updated', 'deleted', 'login', 'logout'
            $table->string('entity_type', 100)->nullable()->index(); // e.g., 'App\Models\User', 'App\Models\Course'
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('module', 50)->nullable()->index(); // e.g., 'users', 'courses', 'finance', 'staff'
            $table->json('old_values')->nullable(); // Previous values (for updates)
            $table->json('new_values')->nullable(); // New values (for creates/updates)
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable(); // HTTP method
            $table->unsignedSmallInteger('status_code')->nullable(); // HTTP status code
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite indexes for common queries
            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
