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
        Schema::create('friendship_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('friendship_id');
            $table->string('friend_type');
            $table->unsignedBigInteger('friend_id');
            $table->unsignedInteger('group_id');

            $table->index(['friend_type', 'friend_id']);
            $table->unique(['friendship_id', 'friend_id', 'friend_type', 'group_id'], 'unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendship_groups');
    }
};
