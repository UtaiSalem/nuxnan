<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $orphanCount = DB::table('student_cards')->whereNull('student_id')->count();
        if ($orphanCount > 0) {
            throw new RuntimeException("Cannot enforce student card constraints: {$orphanCount} orphan card(s) must be reconciled first.");
        }

        // Check if there is an existing foreign key on student_id to drop
        try {
            Schema::table('student_cards', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
            });
        } catch (\Exception $e) {
            // Ignore if foreign key doesn't exist
        }

        Schema::table('student_cards', function (Blueprint $table) {
            // Make student_id not null
            $table->bigInteger('student_id')->unsigned()->nullable(false)->change();

            // Add new foreign key with cascade onDelete
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            if (!Schema::hasColumn('student_cards', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            }

            // Add generated column for active state unique constraint if not exists
            if (!Schema::hasColumn('student_cards', 'is_active_flag')) {
                $table->boolean('is_active_flag')->virtualAs("CASE WHEN student_status = 'active' THEN 1 ELSE NULL END");
            }
        });

        // Add unique constraint if not exists
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();
        $driver = $conn->getDriverName();
        
        $indexExists = false;
        if ($driver === 'sqlite') {
            try {
                $indexes = DB::select("PRAGMA index_list('student_cards')");
                foreach ($indexes as $index) {
                    if ($index->name === 'uq_student_card_active') {
                        $indexExists = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Fallback
            }
        } else {
            $indexExistsQueryResult = DB::select("
                SELECT INDEX_NAME 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'student_cards' 
                AND INDEX_NAME = 'uq_student_card_active'
            ", [$dbName]);
            $indexExists = !empty($indexExistsQueryResult);
        }

        if (!$indexExists) {
            Schema::table('student_cards', function (Blueprint $table) {
                $table->unique(['student_id', 'academy_id', 'is_active_flag'], 'uq_student_card_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_cards', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_student_card_active');
            } catch (\Exception $e) {}
            try {
                $table->dropColumn('is_active_flag');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['student_id']);
            } catch (\Exception $e) {}
        });

        Schema::table('student_cards', function (Blueprint $table) {
            $table->bigInteger('student_id')->unsigned()->nullable()->change();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
        });
    }
};
