<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardian_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('guardian_id')->index();
            $table->string('guardian_type', 50)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->unsignedBigInteger('appointed_by_user_id')->nullable();
            $table->string('appointed_by_role', 30)->nullable();
            $table->timestamp('appointed_at')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('legacy_student_guardian_id')->nullable()->index();
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');
            $table->unique(['student_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardian_links');
    }
};
