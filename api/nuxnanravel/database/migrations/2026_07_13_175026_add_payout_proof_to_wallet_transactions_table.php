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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('payout_proof_path')->nullable();
            $table->string('payout_proof_original_name')->nullable();
            $table->string('payout_proof_mime', 100)->nullable();
            $table->unsignedInteger('payout_proof_size')->nullable();
            $table->foreignId('payout_proof_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payout_proof_uploaded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['payout_proof_uploaded_by']);
            $table->dropColumn([
                'payout_proof_path',
                'payout_proof_original_name',
                'payout_proof_mime',
                'payout_proof_size',
                'payout_proof_uploaded_by',
                'payout_proof_uploaded_at',
            ]);
        });
    }
};
