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
        Schema::create('academy_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_id')->index('academy_settings_academy_id_foreign');
            $table->boolean('auto_accept_members')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_settings');
    }
};
