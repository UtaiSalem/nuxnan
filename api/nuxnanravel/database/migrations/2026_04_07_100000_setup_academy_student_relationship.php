<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setup Academy-Student relationship (Option A: Direct FK)
     *
     * 1. Convert related tables from MyISAM → InnoDB (required for FK constraints)
     * 2. Add academy_id + user_id to students table
     * 3. Add FK constraints for data integrity
     * 4. Add indexes for query performance
     */
    public function up(): void
    {
        // Step 1: Convert tables from MyISAM to InnoDB
        if (DB::getDriverName() === 'mysql') {
            $tablesToConvert = [
                'users',
                'academies',
                'academy_members',
                'students',
            ];

            foreach ($tablesToConvert as $table) {
                if (Schema::hasTable($table)) {
                    $engine = DB::selectOne('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
                    if ($engine && strtolower($engine->ENGINE) !== 'innodb') {
                        DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
                    }
                }
            }
        }

        // Step 2: Add academy_id and user_id to students table (if not exists)
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'academy_id')) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('students', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('academy_id');
            }
        });

        // Step 3: Add indexes
        Schema::table('students', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                // Check if index already exists before adding
                $indexes = collect(DB::select("SHOW INDEX FROM students WHERE Key_name = 'students_academy_id_index'"));
                if ($indexes->isEmpty()) {
                    $table->index('academy_id', 'students_academy_id_index');
                }

                $indexes2 = collect(DB::select("SHOW INDEX FROM students WHERE Key_name = 'students_user_id_index'"));
                if ($indexes2->isEmpty()) {
                    $table->index('user_id', 'students_user_id_index');
                }
            } else {
                // For SQLite/others, assume we need them
                $table->index('academy_id', 'students_academy_id_index');
                $table->index('user_id', 'students_user_id_index');
            }
        });

        // Step 4: Add FK constraints
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('academy_id', 'fk_students_academy_id')
                ->references('id')->on('academies')
                ->onDelete('set null');

            $table->foreign('user_id', 'fk_students_user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });

        Schema::table('academy_members', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                // Add index on student_id if not exists
                $indexes = collect(DB::select("SHOW INDEX FROM academy_members WHERE Key_name = 'academy_members_student_id_index'"));
                if ($indexes->isEmpty()) {
                    $table->index('student_id', 'academy_members_student_id_index');
                }
            } else {
                $table->index('student_id', 'academy_members_student_id_index');
            }

            $table->foreign('student_id', 'fk_academy_members_student_id')
                ->references('id')->on('students')
                ->onDelete('set null');

            // Add FK for academy_id if not exists
            $addFk = true;
            if (DB::getDriverName() === 'mysql') {
                $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academy_members' AND CONSTRAINT_NAME = 'fk_academy_members_academy_id'"));
                $addFk = $fks->isEmpty();
            }

            if ($addFk) {
                $table->foreign('academy_id', 'fk_academy_members_academy_id')
                    ->references('id')->on('academies')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FK constraints
        Schema::table('academy_members', function (Blueprint $table) {
            $table->dropForeign('fk_academy_members_student_id');
            $table->dropForeign('fk_academy_members_academy_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign('fk_students_academy_id');
            $table->dropForeign('fk_students_user_id');
            $table->dropColumn(['academy_id', 'user_id']);
        });

        // Revert to MyISAM (optional - usually you wouldn't)
        // DB::statement("ALTER TABLE `students` ENGINE = MyISAM");
        // DB::statement("ALTER TABLE `academy_members` ENGINE = MyISAM");
        // DB::statement("ALTER TABLE `academies` ENGINE = MyISAM");
    }
};
