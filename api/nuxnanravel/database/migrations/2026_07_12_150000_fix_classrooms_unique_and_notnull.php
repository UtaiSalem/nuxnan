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
        $dbName = DB::getDatabaseName();
        $fkExists = false;

        if (DB::getDriverName() === 'mysql') {
            $fkExists = count(DB::select("
                SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                  AND TABLE_NAME = 'classrooms' 
                  AND CONSTRAINT_NAME = 'classrooms_academic_year_id_foreign'
                  AND CONSTRAINT_SCHEMA = ?
            ", [$dbName])) > 0;
        }

        Schema::table('classrooms', function (Blueprint $table) use ($fkExists) {
            if ($fkExists) {
                $table->dropForeign(['academic_year_id']);
            }
            // In SQLite, unique indexes can be dropped by dropping the unique index constraint
            try {
                $table->dropUnique('classrooms_unique');
            } catch (Exception $e) {
                // Ignore if unique index doesn't exist in some environments
            }
        });

        Schema::table('classrooms', function (Blueprint $table) {
            // Make academic_year_id NOT NULL
            $table->unsignedBigInteger('academic_year_id')->nullable(false)->change();

            // Recreate foreign key with restrict
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');

            // Recreate unique key
            $table->unique(['academy_id', 'academic_year_id', 'grade_level', 'section'], 'classrooms_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        $fkExists = false;

        if (DB::getDriverName() === 'mysql') {
            $fkExists = count(DB::select("
                SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                  AND TABLE_NAME = 'classrooms' 
                  AND CONSTRAINT_NAME = 'classrooms_academic_year_id_foreign'
                  AND CONSTRAINT_SCHEMA = ?
                ", [$dbName])) > 0;
        }

        Schema::table('classrooms', function (Blueprint $table) use ($fkExists) {
            if ($fkExists) {
                $table->dropForeign(['academic_year_id']);
            }
            try {
                $table->dropUnique('classrooms_unique');
            } catch (Exception $e) {
                // Ignore
            }
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable(true)->change();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            $table->unique(['academy_id', 'academic_year_id', 'grade_level', 'section'], 'classrooms_unique');
        });
    }
};
