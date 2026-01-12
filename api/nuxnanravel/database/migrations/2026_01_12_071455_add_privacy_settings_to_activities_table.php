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
        Schema::table('activities', function (Blueprint $table) {
            // Privacy settings: 1 = Only Me, 2 = Friends, 3 = Global/Public
            $table->tinyInteger('privacy_settings')->default(3)->after('activity_details');
            $table->index('privacy_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['privacy_settings']);
            $table->dropColumn('privacy_settings');
        });
    }
};
