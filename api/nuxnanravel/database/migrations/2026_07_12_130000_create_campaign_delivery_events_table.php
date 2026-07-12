<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advert_id')->constrained('adverts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->default('impression');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('placement', 50)->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['advert_id', 'event_type']);
            $table->index(['user_id', 'created_at']);
            $table->unique(['advert_id', 'user_id', 'idempotency_key'], 'campaign_delivery_events_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_delivery_events');
    }
};
