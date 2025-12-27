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
        Schema::create('course_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index('course_groups_user_id_foreign');
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('status', ['0', '1'])->default('0')->comment('0 =\'normal\', 1=\'closed\'');
            $table->tinyInteger('auto_accept_member')->nullable()->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_groups');
    }
};
