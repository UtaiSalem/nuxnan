<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('community_memberships', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('community_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('role')->nullable();
            $table->timestamp('join_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('last_active')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('community_memberships');
    }
};
