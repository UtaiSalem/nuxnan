<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('account_status', 30)->default('pending_activation')->after('user_id')->index();
        });

        DB::table('students')
            ->whereNotNull('user_id')
            ->update(['account_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['account_status']);
            $table->dropColumn('account_status');
        });
    }
};
