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
        Schema::create('community_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_id')->index('community_memberships_community_id_foreign');
            $table->unsignedBigInteger('user_id')->index('community_memberships_user_id_foreign');
            $table->string('role')->default('member');
            $table->timestamp('join_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_active')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_memberships');
    }
};
