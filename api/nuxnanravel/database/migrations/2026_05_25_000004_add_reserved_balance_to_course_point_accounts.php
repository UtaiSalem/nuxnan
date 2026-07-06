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
        Schema::table('course_point_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('reserved_balance')->default(0)
                ->after('total_distributed')
                ->comment('ยอดแต้มที่จองไว้สำหรับ campaigns ที่กำหนด max_claims');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_point_accounts', function (Blueprint $table) {
            $table->dropColumn('reserved_balance');
        });
    }
};
