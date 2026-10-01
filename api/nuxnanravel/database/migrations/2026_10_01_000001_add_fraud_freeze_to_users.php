<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a reversible fraud-freeze to users so an admin can temporarily lock a
 * member's points wallet (pp) and/or money wallet while investigating fraud.
 * A non-null *_frozen_at means that wallet is locked; clearing it unfreezes.
 * Columns are added idempotently (dev/prod dumps have drifted in this project).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'points_frozen_at')) {
                $table->timestamp('points_frozen_at')->nullable()->after('locked_balance');
            }
            if (! Schema::hasColumn('users', 'wallet_frozen_at')) {
                $table->timestamp('wallet_frozen_at')->nullable()->after('points_frozen_at');
            }
            if (! Schema::hasColumn('users', 'freeze_reason')) {
                $table->string('freeze_reason', 500)->nullable()->after('wallet_frozen_at');
            }
            if (! Schema::hasColumn('users', 'frozen_by')) {
                $table->unsignedBigInteger('frozen_by')->nullable()->after('freeze_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['points_frozen_at', 'wallet_frozen_at', 'freeze_reason', 'frozen_by'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
