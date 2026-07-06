<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First change to string
        Schema::table('student_cards', function (Blueprint $table) {
            $table->string('student_status', 20)->nullable()->change();
            $table->string('profile_image', 255)->nullable()->change();
        });

        // Then update the values
        DB::statement('UPDATE student_cards SET student_status = "active" WHERE student_status = "1" OR student_status IS NULL OR student_status = "active"');
        DB::statement('UPDATE student_cards SET student_status = "expired" WHERE student_status = "0" OR student_status = "expired"');

        try {
            Schema::table('student_cards', function (Blueprint $table) {
                $table->index(['academy_id', 'class_level', 'class_section', 'student_status'], 'student_cards_academy_id_class_level_class_section_status_idx');
            });
        } catch (Exception $e) {
            // Index might already exist
        }
    }

    public function down(): void
    {
        Schema::table('student_cards', function (Blueprint $table) {
            $table->dropIndex('student_cards_academy_id_class_level_class_section_status_idx');
        });
    }
};
