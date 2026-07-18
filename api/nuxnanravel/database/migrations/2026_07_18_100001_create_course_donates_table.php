<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_donates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('donor_display_name', 255)->nullable();
            $table->string('donation_type', 16);
            $table->unsignedBigInteger('points_amount')->nullable();
            $table->decimal('cash_amount', 18, 4)->nullable();
            $table->char('currency', 3)->default('THB');
            $table->string('status', 16)->default('pending');
            $table->text('purpose')->nullable();
            $table->boolean('anonymous')->default(false);
            $table->string('slip_path')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('course_point_transaction_id')->nullable()->constrained('course_point_transactions')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['course_id', 'status', 'created_at']);
            $table->index('donor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_donates');
    }
};
