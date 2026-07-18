<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_delivery_events', function (Blueprint $table) {
            $table->string('session_id', 64)->nullable()->unique();
            $table->string('delivery_token_hash', 64)->nullable()->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('required_duration')->nullable();
            $table->decimal('page_visibility_ratio', 5, 4)->nullable();
            $table->string('device_fingerprint_hash', 64)->nullable();
            $table->string('status', 16)->nullable();
            $table->string('fraud_reason', 64)->nullable();
            $table->index(['advert_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_delivery_events', function (Blueprint $table) {
            $table->dropIndex(['advert_id', 'user_id', 'status']);
            $table->dropUnique(['session_id']);
            $table->dropUnique(['delivery_token_hash']);
            $table->dropColumn([
                'session_id', 'delivery_token_hash', 'started_at', 'last_heartbeat_at',
                'completed_at', 'required_duration', 'page_visibility_ratio',
                'device_fingerprint_hash', 'status', 'fraud_reason',
            ]);
        });
    }
};
