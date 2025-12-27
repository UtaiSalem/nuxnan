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
        Schema::create('course_group_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id')->index('course_group_members_course_id_foreign');
            $table->unsignedBigInteger('group_id')->index('course_group_members_group_id_foreign');
            $table->unsignedBigInteger('user_id')->index('course_group_members_user_id_foreign');
            $table->enum('status', ['0', '1'])->default('0');
            $table->integer('last_accessed_tab')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_group_members');
    }
};
