<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add transfer_in and transfer_out to the enum values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE points_transactions MODIFY COLUMN transaction_type ENUM('earn', 'spend', 'refund', 'transfer', 'admin_adjust', 'conversion', 'transfer_in', 'transfer_out') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE points_transactions MODIFY COLUMN transaction_type ENUM('earn', 'spend', 'refund', 'transfer', 'admin_adjust', 'conversion') NOT NULL");
        }
    }
};
