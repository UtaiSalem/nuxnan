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
        if (! Schema::hasColumn('course_groups', 'classroom_synced_at')) {
            Schema::table('course_groups', function (Blueprint $table) {
                $table->timestamp('classroom_synced_at')->nullable()->after('sort_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('course_groups', 'classroom_synced_at')) {
            Schema::table('course_groups', function (Blueprint $table) {
                $table->dropColumn('classroom_synced_at');
            });
        }
    }
};
