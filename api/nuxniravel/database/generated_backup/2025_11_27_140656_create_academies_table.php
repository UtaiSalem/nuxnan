<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('academies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slogan')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('director')->nullable();
            $table->integer('established_year')->nullable();
            $table->string('type')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('accreditation_body')->nullable();
            $table->integer('total_students')->nullable();
            $table->integer('total_teachers')->nullable();
            $table->integer('membership_fees_points')->nullable();
            $table->integer('courses_offered')->nullable();
            $table->text('facilities')->nullable();
            $table->text('academy_timings')->nullable();
            $table->text('holidays')->nullable();
            $table->string('social_media_links')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academies');
    }
};
