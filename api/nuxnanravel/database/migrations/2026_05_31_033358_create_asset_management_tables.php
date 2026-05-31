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
        // School Assets
        Schema::create('school_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category')->nullable(); // furniture, IT, lab, sport
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'maintenance', 'retired', 'lost'])->default('active');
            $table->string('condition')->nullable(); // new, good, fair, poor
            $table->timestamps();
            
            $table->index(['academy_id', 'category']);
            $table->index('asset_code');
        });

        // Maintenance Logs
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('issue_description');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('cost', 10, 2)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_logs');
        Schema::dropIfExists('school_assets');
    }
};
