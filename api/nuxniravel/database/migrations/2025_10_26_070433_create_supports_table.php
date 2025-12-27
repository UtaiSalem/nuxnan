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
        Schema::create('supports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('supports_user_id_foreign');
            $table->unsignedBigInteger('supporter')->nullable();
            $table->integer('amounts');
            $table->integer('duration');
            $table->integer('total_views');
            $table->integer('remaining_views');
            $table->string('slip');
            $table->string('media_image');
            $table->string('media_link')->nullable();
            $table->timestamp('transfer_date')->useCurrentOnUpdate()->useCurrent();
            $table->string('transfer_time');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('privacy_settings')->default(3);
            $table->integer('exchange_points')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supports');
    }
};
