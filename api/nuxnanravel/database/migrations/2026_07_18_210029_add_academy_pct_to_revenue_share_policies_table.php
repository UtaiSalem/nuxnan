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
        Schema::table('revenue_share_policies', function (Blueprint $table) {
            $table->decimal('academy_pct', 5, 2)->default(0.00)->after('course_pct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenue_share_policies', function (Blueprint $table) {
            $table->dropColumn('academy_pct');
        });
    }
};
