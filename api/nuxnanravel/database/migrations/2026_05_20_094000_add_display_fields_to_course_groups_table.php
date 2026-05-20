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
        $afterColumn = Schema::hasColumn('course_groups', 'privacy') ? 'privacy' : 'status';

        Schema::table('course_groups', function (Blueprint $table) use ($afterColumn) {
            if (! Schema::hasColumn('course_groups', 'color')) {
                $table->string('color', 7)->nullable()->after($afterColumn);
            }

            if (! Schema::hasColumn('course_groups', 'max_members')) {
                $table->unsignedInteger('max_members')->nullable()->after('color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_groups', function (Blueprint $table) {
            if (Schema::hasColumn('course_groups', 'max_members')) {
                $table->dropColumn('max_members');
            }

            if (Schema::hasColumn('course_groups', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
