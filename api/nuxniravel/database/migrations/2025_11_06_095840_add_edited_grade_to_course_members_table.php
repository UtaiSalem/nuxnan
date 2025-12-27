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
            $table->integer('edited_grade')->nullable()->default(null)->after('grade_progress')->comment('Grade edited after course completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->dropColumn('edited_grade');
        });
    }
};
