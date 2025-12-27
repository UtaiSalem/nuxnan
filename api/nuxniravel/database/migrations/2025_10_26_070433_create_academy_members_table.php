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
        Schema::create('academy_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('member_code')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('role')->nullable();
            $table->date('enrollment_date')->default('2024-05-04');
            $table->date('graduation_date')->nullable();
            $table->enum('graduation_reason', ['completed', 'expelled', 'dropped_out', 'transferred', 'other'])->nullable();
            $table->text('note_comment')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_members');
    }
};
