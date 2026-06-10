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
        Schema::table('course_purchases', function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('academy_id')->nullable()->after('source_course_id');
            $blueprint->foreign('academy_id')->references('id')->on('academies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_purchases', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['academy_id']);
            $blueprint->dropColumn('academy_id');
        });
    }
};
