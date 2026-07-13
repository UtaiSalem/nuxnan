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
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE user_usage_events MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;');
        } else {
            Schema::table('user_usage_events', function (Blueprint $table) {
                $table->bigIncrements('id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE user_usage_events MODIFY id BIGINT UNSIGNED NOT NULL;');
        } else {
            Schema::table('user_usage_events', function (Blueprint $table) {
                $table->bigInteger('id')->unsigned()->change();
            });
        }
    }
};
