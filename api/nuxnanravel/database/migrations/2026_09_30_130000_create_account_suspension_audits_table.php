<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedicated audit trail for economy (points/wallet) suspension actions.
     * Every suspend / restore is recorded here so admins can review the full
     * history of who froze an account, when, and why.
     */
    public function up(): void
    {
        Schema::create('account_suspension_audits', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('user_email')->nullable(); // snapshot at action time

            $table->enum('action', ['suspend', 'restore']);

            // State applied by this action
            $table->boolean('points_suspended')->default(false);
            $table->boolean('wallet_suspended')->default(false);

            $table->text('reason')->nullable();

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_suspension_audits');
    }
};
