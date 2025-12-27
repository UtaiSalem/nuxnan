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
        Schema::create('academies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('academies_user_id_foreign');
            $table->string('name')->unique('name');
            $table->string('slogan')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('director')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('type')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('accreditation_body')->nullable();
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('total_teachers')->default(0);
            $table->unsignedInteger('membership_fees_points')->default(0);
            $table->integer('courses_offered')->nullable()->default(0);
            $table->text('facilities')->nullable();
            $table->text('academy_timings')->nullable();
            $table->text('holidays')->nullable();
            $table->string('social_media_links')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academies');
    }
};
