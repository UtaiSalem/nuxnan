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
        Schema::create('support_viewers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('support_id')->index('support_viewers_support_id_foreign');
            $table->unsignedBigInteger('user_id')->index('support_viewers_user_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_viewers');
    }
};
