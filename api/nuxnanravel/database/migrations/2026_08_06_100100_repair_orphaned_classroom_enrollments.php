<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $batch = '2026_08_06_100100';

    public function up(): void
    {
        if (! Schema::hasTable('classroom_repair_backups')) {
            Schema::create('classroom_repair_backups', function (Blueprint $table) {
                $table->id();
                $table->string('batch', 64);
                $table->string('table_name', 64);
                $table->unsignedBigInteger('record_id');
                $table->json('payload');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['batch', 'table_name'], 'crb_batch_tbl_idx');
            });
        }

        DB::transaction(function () {
            $backup = function (string $table, object|array $row): void {
                $data = (array) $row;
                if (! DB::table('classroom_repair_backups')->where('batch', $this->batch)->where('table_name', $table)->where('record_id', $data['id'])->exists()) {
                    DB::table('classroom_repair_backups')->insert([
                        'batch' => $this->batch,
                        'table_name' => $table,
                        'record_id' => $data['id'],
                        'payload' => json_encode($data, JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                    ]);
                }
            };

            $orphans = DB::table('classroom_students as cs')
                ->leftJoin('classrooms as c', 'c.id', '=', 'cs.classroom_id')
                ->whereNull('c.id')->get(['cs.*']);
            foreach ($orphans as $row) {
                $backup('classroom_students', $row);
            }

            $activeStudentIds = $orphans->where('status', 'active')->pluck('student_id')->unique();
            foreach ($activeStudentIds as $studentId) {
                $student = DB::table('students')->where('id', $studentId)->first();
                if ($student) {
                    $backup('students', $student);
                    DB::table('students')->where('id', $studentId)->update(['status' => 'graduated', 'class_level' => null, 'class_section' => null]);
                }

                $card = DB::table('student_cards')->where('student_id', $studentId)->where('student_status', 'active')->first();
                if ($card) {
                    $backup('student_cards', $card);
                    DB::table('student_cards')->where('id', $card->id)->update(['student_status' => 'graduated']);
                }

                if (Schema::hasTable('student_academic_info')) {
                    foreach (DB::table('student_academic_info')->where('student_id', $studentId)->where('is_current', true)->get() as $info) {
                        $backup('student_academic_info', $info);
                        DB::table('student_academic_info')->where('id', $info->id)->update(['graduation_date' => today(), 'study_status' => 'graduated', 'is_current' => false]);
                    }
                }
            }

            DB::table('classroom_students')->whereIn('id', $orphans->pluck('id'))->delete();

            // Pending-intake students may have class fields before their first active enrollment; re-running this broad repair can clear them.
            $strays = DB::table('students as s')
                ->where(function ($q) {
                    $q->whereNotNull('s.class_level')->orWhereNotNull('s.class_section');
                })
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw('1'))->from('classroom_students as cs')->whereColumn('cs.student_id', 's.id')->where('cs.status', 'active');
                })
                ->get(['s.*']);
            foreach ($strays as $student) {
                $backup('students', $student);
                DB::table('students')->where('id', $student->id)->update(['class_level' => null, 'class_section' => null]);
            }

            Log::info('Repaired orphaned classroom enrollments', [
                'orphans' => $orphans->count(),
                'graduated' => $activeStudentIds->count(),
                'graduated_student_ids' => $activeStudentIds->values()->all(),
                'orphaned_classroom_student_ids' => $orphans->pluck('id')->values()->all(),
                'cleared_students' => $strays->count(),
                'cleared_student_ids' => $strays->pluck('id')->values()->all(),
            ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('classroom_repair_backups')) {
            return;
        }
        DB::transaction(function () {
            $rows = DB::table('classroom_repair_backups')->where('batch', $this->batch)->orderBy('id')->get();
            foreach ($rows->whereIn('table_name', ['students', 'student_cards', 'student_academic_info']) as $row) {
                DB::table($row->table_name)->where('id', $row->record_id)->update((array) json_decode($row->payload, true));
            }
            foreach ($rows->where('table_name', 'classroom_students') as $row) {
                DB::table('classroom_students')->updateOrInsert(['id' => $row->record_id], (array) json_decode($row->payload, true));
            }
            DB::table('classroom_repair_backups')->where('batch', $this->batch)->delete();
        });
    }
};
