<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('academy_id');
            $table->index('classroom_id', 'sc_classroom_idx');
            $table->foreign('classroom_id', 'sc_classroom_fk')
                ->references('id')->on('classrooms')->nullOnDelete();
        });

        foreach (DB::table('academies')->pluck('id') as $academyId) {
            $yearIds = DB::table('academic_years')
                ->where('academy_id', $academyId)
                ->where('is_current', true)
                ->pluck('id');

            foreach ($yearIds as $yearId) {
                DB::table('student_cards')
                    ->where('academy_id', $academyId)
                    ->whereNull('classroom_id')
                    ->whereExists(function ($query) use ($academyId, $yearId) {
                        $query->select(DB::raw('1'))
                            ->from('classroom_students as cs')
                            ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
                            ->whereColumn('cs.student_id', 'student_cards.student_id')
                            ->where('cs.academy_id', $academyId)
                            ->where('cs.academic_year_id', $yearId)
                            ->where('cs.status', 'active');
                    })
                    ->orderBy('id')
                    ->chunkById(100, function ($cards) use ($academyId, $yearId) {
                        foreach ($cards as $card) {
                            $classroomId = DB::table('classroom_students as cs')
                                ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
                                ->where('cs.student_id', $card->student_id)
                                ->where('cs.academy_id', $academyId)
                                ->where('cs.academic_year_id', $yearId)
                                ->where('cs.status', 'active')
                                ->value('cs.classroom_id');

                            if ($classroomId !== null) {
                                DB::table('student_cards')->where('id', $card->id)->update(['classroom_id' => $classroomId]);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        Schema::table('student_cards', function (Blueprint $table) {
            $table->dropForeign('sc_classroom_fk');
            $table->dropIndex('sc_classroom_idx');
            $table->dropColumn('classroom_id');
        });
    }
};
