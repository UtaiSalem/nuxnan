<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
            $table->text('admin_note')->nullable()->after('rejection_reason');
            $table->string('payment_reference', 100)->nullable()->after('admin_note');
            $table->timestamp('processed_at')->nullable()->after('payment_reference');
            $table->timestamp('failed_at')->nullable()->after('processed_at');
            $table->string('idempotency_key', 64)->nullable()->after('failed_at');
            $table->unsignedInteger('version')->default(1)->after('idempotency_key');
            $table->decimal('fee', 10, 2)->default(0)->after('amount');
            $table->decimal('net_amount', 10, 2)->nullable()->after('fee');
            $table->string('destination_type', 20)->nullable()->after('net_amount');
            $table->text('destination_snapshot')->nullable()->after('destination_type');
            $table->index('reviewed_by');
            $table->unique('idempotency_key');
            $table->index(['transaction_type', 'status']);
            $table->index(['user_id', 'transaction_type', 'status']);
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['transaction_type', 'status']);
            $table->dropIndex(['user_id', 'transaction_type', 'status']);
            $table->dropColumn([
                'reviewed_by', 'reviewed_at', 'rejection_reason', 'admin_note',
                'payment_reference', 'processed_at', 'failed_at', 'idempotency_key',
                'version', 'fee', 'net_amount', 'destination_type', 'destination_snapshot',
            ]);
        });
    }
};
