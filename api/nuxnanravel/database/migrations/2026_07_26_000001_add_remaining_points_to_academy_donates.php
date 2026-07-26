<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_donates', function (Blueprint $table) {
            $table->unsignedBigInteger('remaining_points')->nullable()->after('points_amount');
        });
        DB::table('academy_donates')->where('donation_type', 'point')->where('status', 'completed')
            ->update(['remaining_points' => DB::raw('points_amount')]);
    }

    public function down(): void
    {
        Schema::table('academy_donates', fn (Blueprint $table) => $table->dropColumn('remaining_points'));
    }
};
