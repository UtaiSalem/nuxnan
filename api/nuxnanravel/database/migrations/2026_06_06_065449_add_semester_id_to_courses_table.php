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
        if (!Schema::hasColumn('courses', 'semester_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('academy_id');
            });
        }
        
        try {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Log or ignore if foreign key fails for some reason
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
};
