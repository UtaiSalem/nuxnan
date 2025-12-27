<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_cards', function (Blueprint $table) {
            $table->id();
            $table->integer('order_no')->nullable();
            $table->string('full_name_thai')->nullable();
            $table->integer('class_level')->nullable();
            $table->integer('class_section')->nullable();
            $table->string('national_id')->nullable();
            $table->string('student_number')->nullable();
            $table->string('level_and_room')->nullable();
            $table->string('title_name')->nullable();
            $table->string('first_name_thai')->nullable();
            $table->string('last_name_thai')->nullable();
            $table->string('first_name_english')->nullable();
            $table->string('birth_date_string')->nullable();
            $table->date('birth_date')->nullable();
            $table->dateTime('card_issue_date')->nullable();
            $table->dateTime('card_expiry_date')->nullable();
            $table->string('issued_by')->nullable();
            $table->integer('student_status')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_cards');
    }
};
