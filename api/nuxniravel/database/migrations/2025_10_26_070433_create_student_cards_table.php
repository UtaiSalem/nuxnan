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
        Schema::create('student_cards', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_no')->nullable();
            $table->string('full_name_thai', 120)->nullable();
            $table->integer('class_level')->nullable();
            $table->integer('class_section')->nullable();
            $table->string('national_id', 13)->nullable();
            $table->string('student_number', 8)->nullable();
            $table->string('level_and_room', 6)->nullable();
            $table->string('title_name', 14)->nullable();
            $table->string('first_name_thai', 80)->nullable();
            $table->string('last_name_thai', 60)->nullable();
            $table->string('first_name_english', 128)->nullable();
            $table->string('birth_date_string', 14)->nullable();
            $table->date('birth_date')->nullable();
            $table->dateTime('card_issue_date')->useCurrent();
            $table->dateTime('card_expiry_date')->default('2027-05-15 16:12:15');
            $table->string('issued_by', 128)->nullable();
            $table->integer('student_status')->nullable();
            $table->string('profile_image', 28)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_cards');
    }
};
