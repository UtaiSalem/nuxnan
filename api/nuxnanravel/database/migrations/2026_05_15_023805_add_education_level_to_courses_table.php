<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('education_level', 30)->nullable()->after('level');
            $table->tinyInteger('education_year')->unsigned()->nullable()->after('education_level');
        });

        // Normalize existing level data
        $patterns = [
            '/ชั้นประถมศึกษาปีที่\s*(\d+)/' => 'ประถมศึกษา',
            '/ชั้นมัธยมศึกษาปีที่\s*(\d+)/' => 'มัธยมศึกษา',
            '/ประถมศึกษาปีที่\s*(\d+)/'     => 'ประถมศึกษา',
            '/มัธยมศึกษาปีที่\s*(\d+)/'     => 'มัธยมศึกษา',
            '/^ป\.?\s*(\d+)$/'              => 'ประถมศึกษา',
            '/^ม\.?\s*(\d+)$/'              => 'มัธยมศึกษา',
            '/ปวช/i'                         => 'ปวช.',
            '/ปวส/i'                         => 'ปวส.',
            '/อุดมศึกษา/i'                   => 'อุดมศึกษา',
            '/อนุบาล/i'                      => 'อื่นๆ',
        ];

        $courses = DB::table('courses')->whereNotNull('level')->where('level', '!=', '')->get(['id', 'level']);

        foreach ($courses as $course) {
            $level = trim($course->level);
            $educationLevel = null;
            $educationYear = null;

            foreach ($patterns as $pattern => $groupName) {
                if (preg_match($pattern, $level, $matches)) {
                    $educationLevel = $groupName;
                    if (isset($matches[1])) {
                        $educationYear = (int) $matches[1];
                    }
                    break;
                }
            }

            if (!$educationLevel && $level !== '') {
                $educationLevel = 'อื่นๆ';
            }

            DB::table('courses')->where('id', $course->id)->update([
                'education_level' => $educationLevel,
                'education_year'  => $educationYear,
            ]);
        }

    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'education_year']);
        });
    }
};
