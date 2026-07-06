<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'use_legacy_gradebook')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('use_legacy_gradebook')->default(false)->after('total_score');
        });

        DB::statement('
            UPDATE courses SET use_legacy_gradebook = 1
            WHERE id IN (SELECT DISTINCT course_id FROM gradebook_assessments)
        ');
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('use_legacy_gradebook');
        });
    }
};
