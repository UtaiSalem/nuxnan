<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('academy_member_id')->nullable()->constrained('academy_members')->nullOnDelete();
            $table->string('member_code', 20)->nullable();
            $table->string('display_name', 150);
            $table->enum('voter_type', ['student', 'staff']);
            $table->string('grade_level', 10)->nullable();
            $table->string('classroom_name', 50)->nullable();
            $table->string('student_number', 20)->nullable();
            $table->timestamps();
            $table->unique(['election_id', 'user_id']);
            $table->index(['election_id', 'grade_level']);
            $table->index(['election_id', 'member_code']);
            $table->index(['election_id', 'student_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_voters');
    }
};
