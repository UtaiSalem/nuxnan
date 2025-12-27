<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->string('member_name')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->boolean('course_member_status')->nullable();
            $table->bigInteger('group_id')->nullable();
            $table->boolean('group_member_status')->nullable();
            $table->timestamp('enrollment_date')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('role')->nullable();
            $table->integer('order_number')->nullable();
            $table->integer('member_code')->nullable();
            $table->integer('achieved_score')->nullable();
            $table->integer('bonus_points')->nullable();
            $table->integer('efficiency')->nullable();
            $table->string('grade_progress')->nullable();
            $table->integer('edited_grade')->nullable();
            $table->boolean('member_status')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->timestamp('access_expiry_date')->nullable();
            $table->text('notes_comments')->nullable();
            $table->boolean('last_accessed_tab')->nullable();
            $table->boolean('last_accessed_group_tab')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_members');
    }
};
