<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Member-submitted fraud reports against another account. These feed the
     * admin review queue, which can freeze the reported account's economy
     * (points/wallet) via the existing suspension flow.
     */
    public function up(): void
    {
        Schema::create('account_fraud_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();

            // scam | phishing | point_fraud | money_fraud | fake_account | other
            $table->string('category', 40);
            $table->text('description');

            // Optional link to the transaction that triggered the report.
            $table->string('related_transaction_type', 20)->nullable(); // points | wallet
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->text('evidence_note')->nullable();

            // pending | reviewing | action_taken | dismissed
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->foreign('handled_by')->references('id')->on('users')->nullOnDelete();

            $table->index('reported_user_id');
            $table->index('reporter_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_fraud_reports');
    }
};
