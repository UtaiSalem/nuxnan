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
        Schema::table('course_members', function (Blueprint $table) {
            // Change grade_progress column to string to support both numeric grades and "ร." for incomplete
            $table->string('grade_progress')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            // Revert back to integer
            $table->integer('grade_progress')->nullable()->default(0)->change();
        });
    }
};
