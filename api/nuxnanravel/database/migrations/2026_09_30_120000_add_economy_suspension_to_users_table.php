<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fraud-suspension flags for the points and wallet systems.
     * Learning access is unaffected — these only freeze the economy.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('points_suspended')->default(false)->after('total_points_spent');
            $table->boolean('wallet_suspended')->default(false)->after('points_suspended');
            $table->text('economy_suspended_reason')->nullable()->after('wallet_suspended');
            $table->timestamp('economy_suspended_at')->nullable()->after('economy_suspended_reason');
            $table->unsignedBigInteger('economy_suspended_by')->nullable()->after('economy_suspended_at');

            $table->index('points_suspended');
            $table->index('wallet_suspended');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['points_suspended']);
            $table->dropIndex(['wallet_suspended']);
            $table->dropColumn([
                'points_suspended',
                'wallet_suspended',
                'economy_suspended_reason',
                'economy_suspended_at',
                'economy_suspended_by',
            ]);
        });
    }
};
