<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('academy_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('academy_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('member_code')->nullable();
            $table->boolean('status')->nullable();
            $table->string('role')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('graduation_reason')->nullable();
            $table->text('note_comment')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academy_members');
    }
};
