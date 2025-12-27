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
        Schema::create('communities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('creater_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('creator_id')->index('communities_creator_id_foreign');
            $table->timestamp('creation_date')->nullable();
            $table->string('privacy_settings')->default('public');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('member_count')->default(0);
            $table->text('rules')->nullable();
            $table->string('community_picture')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
