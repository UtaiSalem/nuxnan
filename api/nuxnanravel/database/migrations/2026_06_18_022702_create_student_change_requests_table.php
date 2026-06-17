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
        Schema::create('student_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id');
            $table->string('model_type')->comment('Student|StudentAddress|StudentContact|etc');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('field');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('status')->default('pending')->comment('pending|approved|rejected');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('requested_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_change_requests');
    }
};
