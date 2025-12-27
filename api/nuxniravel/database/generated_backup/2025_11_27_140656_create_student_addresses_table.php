<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_addresses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->nullable();
            $table->string('address_type')->nullable();
            $table->string('house_number')->nullable();
            $table->string('village_number')->nullable();
            $table->string('village_name')->nullable();
            $table->string('alley')->nullable();
            $table->string('road')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_addresses');
    }
};
