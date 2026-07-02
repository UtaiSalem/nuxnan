<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE student_home_visits MODIFY visit_status ENUM('completed','pending','rescheduled','scheduled','cancelled','in-progress') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('student_home_visits')->where('visit_status', 'scheduled')->update(['visit_status' => 'pending']);
        DB::table('student_home_visits')->whereIn('visit_status', ['cancelled', 'in-progress'])->update(['visit_status' => 'rescheduled']);
        DB::statement("ALTER TABLE student_home_visits MODIFY visit_status ENUM('completed','pending','rescheduled') NOT NULL DEFAULT 'completed'");
    }
};
