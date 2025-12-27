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
        Schema::create('friendships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->string('status')->default('pending')->comment('pending/accepted/denied/blocked/');
            $table->bigInteger('action_user_id')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
