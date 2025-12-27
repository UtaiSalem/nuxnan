<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guardian_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('guardian_id')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('contact_value')->nullable();
            $table->boolean('is_primary')->nullable();
            $table->boolean('is_verified')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guardian_contacts');
    }
};
