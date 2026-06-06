<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_grades', function (Blueprint $table) {
            $table->decimal('max_score', 10, 2)->default(0)->after('total_score');
        });

        DB::statement("
            UPDATE course_grades cg
            JOIN courses c ON cg.course_id = c.id
            SET cg.max_score = c.total_score
        ");
    }

    public function down(): void
    {
        Schema::table('course_grades', function (Blueprint $table) {
            $table->dropColumn('max_score');
        });
    }
};
