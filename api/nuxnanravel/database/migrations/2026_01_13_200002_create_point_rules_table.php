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
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key')->unique();
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->enum('action_type', ['earn', 'spend']);
            $table->string('source_type');
            $table->decimal('base_amount', 10, 2);
            $table->decimal('multiplier', 5, 2)->default(1.00);
            $table->integer('max_daily_earnings')->nullable();
            $table->integer('max_monthly_earnings')->nullable();
            $table->integer('cooldown_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('rule_key');
            $table->index('source_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
