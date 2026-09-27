<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `campaign_delivery_events.status` from varchar(16) to varchar(32).
 *
 * 2026_07_18_210001_widen_campaign_delivery_events_for_sessions added the column
 * as `string('status', 16)`, but the model's own status vocabulary outgrew it:
 * CampaignDeliveryEvent::STATUS_INSUFFICIENT_VISIBILITY ('insufficient_visibility')
 * is 23 characters and STATUS_INSUFFICIENT_WATCH ('insufficient_watch') is 18, so
 * every completion that resolves to one of those dies on MySQL with
 * `1406 Data too long for column 'status'`. SQLite does not enforce the length,
 * so the tests stayed green there. 32 leaves headroom for the current longest (23).
 *
 * `status` is part of the index (advert_id, user_id, status); a varchar length
 * change is an in-place MODIFY that keeps the index intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_delivery_events', function (Blueprint $table) {
            $table->string('status', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_delivery_events', function (Blueprint $table) {
            $table->string('status', 16)->nullable()->change();
        });
    }
};
