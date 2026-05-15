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
        // 1. Normalize academic_year: VARCHAR → SMALLINT UNSIGNED (Thai Year)
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('academic_year_new')->nullable()->after('academic_year');
        });

        DB::table('courses')->whereNotNull('academic_year')->get(['id', 'academic_year'])->each(function ($c) {
            $y = (int) preg_replace('/\D/', '', $c->academic_year); // Extract digits
            
            if ($y >= 2560 && $y <= 2580) {
                // Already in Thai year range
            } elseif ($y >= 2017 && $y <= 2037) {
                $y += 543; // Convert AD to BE
            } else {
                $y = null; // Out of range or invalid
            }
            
            DB::table('courses')->where('id', $c->id)->update(['academic_year_new' => $y]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
        
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('academic_year_new', 'academic_year');
        });

        // 2. Normalize semester: Map existing values to standard codes
        $semesterMap = [
            'เสา-อาทิตย์' => 'weekend',
            'เสาร์-อาทิตย์' => 'weekend',
            'ภาคฤดูร้อน' => 'summer',
            'ภาคเรียนที่ 1' => '1',
            'ภาคเรียนที่ 2' => '2',
            'ภาคเรียนที่ 3' => '3',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            'summer' => 'summer',
            'weekend' => 'weekend',
        ];

        // Update values to standard codes
        foreach ($semesterMap as $old => $new) {
            DB::table('courses')->where('semester', $old)->update(['semester' => $new]);
        }

        // Set others to null if not in our new standard (optional, but cleaner)
        $standardSemesters = ['1', '2', '3', 'summer', 'weekend'];
        DB::table('courses')
            ->whereNotNull('semester')
            ->whereNotIn('semester', $standardSemesters)
            ->update(['semester' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('academic_year_old', 20)->nullable()->after('academic_year');
        });

        DB::table('courses')->whereNotNull('academic_year')->get(['id', 'academic_year'])->each(function ($c) {
            DB::table('courses')->where('id', $c->id)->update(['academic_year_old' => (string)$c->academic_year]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
        
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('academic_year_old', 'academic_year');
        });
    }
};
