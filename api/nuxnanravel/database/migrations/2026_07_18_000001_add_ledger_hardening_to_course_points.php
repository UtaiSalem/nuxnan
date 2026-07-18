<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_point_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique();
        });

        Schema::table('course_point_accounts', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('course_point_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('course_point_accounts', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
