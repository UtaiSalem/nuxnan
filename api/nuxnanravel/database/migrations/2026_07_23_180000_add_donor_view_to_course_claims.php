<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE course_donates ENGINE=InnoDB');
        }
        if (! Schema::hasColumn('course_donates', 'remaining_points')) {
            Schema::table('course_donates', function (Blueprint $table) {
                $table->unsignedBigInteger('remaining_points')->nullable()->default(0);
            });
        }

        DB::table('course_donates')
            ->where('donation_type', 'point')
            ->whereIn('status', ['approved', 'completed'])
            ->update(['remaining_points' => DB::raw('points_amount')]);

        if (! Schema::hasColumn('course_point_campaign_claims', 'viewed_donor_id')) {
            Schema::table('course_point_campaign_claims', function (Blueprint $table) {
                $table->unsignedBigInteger('viewed_donor_id')->nullable();
                $table->unsignedBigInteger('viewed_donation_id')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->foreign('viewed_donor_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('viewed_donation_id')->references('id')->on('course_donates')->nullOnDelete();
                $table->index('viewed_donor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('course_point_campaign_claims', function (Blueprint $table) {
            $table->dropForeign(['viewed_donor_id']);
            $table->dropForeign(['viewed_donation_id']);
            $table->dropIndex(['viewed_donor_id']);
            $table->dropColumn(['viewed_donor_id', 'viewed_donation_id', 'viewed_at']);
        });

        Schema::table('course_donates', function (Blueprint $table) {
            $table->dropColumn('remaining_points');
        });
    }
};
