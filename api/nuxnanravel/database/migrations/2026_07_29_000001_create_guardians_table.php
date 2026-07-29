<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('citizen_id', 20)->nullable()->index();
            $table->string('title_prefix', 20)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('occupation', 100)->nullable();
            $table->string('workplace', 200)->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('nationality', 50)->default('ไทย');
            $table->enum('status', ['alive', 'deceased', 'unknown'])->default('alive');
            $table->json('legacy_row_ids')->nullable();
            $table->timestamps();
            $table->index(['academy_id', 'citizen_id']);
            $table->index(['academy_id', 'first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
