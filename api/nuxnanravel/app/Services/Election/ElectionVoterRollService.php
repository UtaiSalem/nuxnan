<?php

namespace App\Services\Election;

use App\Models\AcademyMember;
use App\Models\Election;
use App\Models\ElectionVoter;
use App\Models\MemberActivityLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ElectionVoterRollService
{
    public function lock(Election $e, User $actor): array
    {
        if (! in_array($e->status, [Election::STATUS_DRAFT, Election::STATUS_NOMINATION, Election::STATUS_CAMPAIGN], true)) {
            throw new DomainException('ไม่สามารถล็อกบัญชีผู้มีสิทธิ์หลังเริ่มลงคะแนนได้');
        }
        $eligible = AcademyMember::query()
            ->where('academy_id', $e->academy_id)
            ->where('status', 2)
            ->whereNotNull('user_id')
            ->where(function ($query) {
                $query->whereNull('student_id')
                    ->orWhereExists(function ($student) {
                        $student->selectRaw('1')
                            ->from('students')
                            ->whereColumn('students.id', 'academy_members.student_id')
                            ->where('students.status', 'active');
                    });
            });
        $skippedNoUserAccount = AcademyMember::query()->where('academy_id', $e->academy_id)->where('status', 2)->whereNull('user_id')->count();
        $skippedInactiveStudent = AcademyMember::query()
            ->where('academy_id', $e->academy_id)
            ->where('status', 2)
            ->whereNotNull('user_id')
            ->whereNotNull('student_id')
            ->whereNotExists(function ($student) {
                $student->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.id', 'academy_members.student_id')
                    ->where('students.status', 'active');
            })->count();
        $ids = $eligible->pluck('user_id');
        ElectionVoter::where('election_id', $e->id)->whereNotIn('user_id', $ids)->delete();
        $duplicateMemberRows = (clone $eligible)->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->count();
        $eligible->where(function ($query) {
            $query->whereNotNull('student_id')
                ->orWhereNotExists(function ($preferred) {
                    $preferred->selectRaw('1')
                        ->from('academy_members as preferred_members')
                        ->whereColumn('preferred_members.user_id', 'academy_members.user_id')
                        ->where('preferred_members.status', 2)
                        ->whereNotNull('preferred_members.user_id')
                        ->whereNotNull('preferred_members.student_id');
                });
        });
        $eligible->with('user')->chunkById(500, function ($members) use ($e) {
            $studentIds = $members->pluck('student_id')->filter();
            $enrolments = DB::table('classroom_students')->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
                ->whereIn('classroom_students.student_id', $studentIds)->where('classroom_students.status', 'active')
                ->where('classroom_students.academic_year_id', $e->academic_year_id ?: DB::table('academic_years')->where('academy_id', $e->academy_id)->where('is_current', 1)->value('id'))
                ->get(['classroom_students.student_id', 'classroom_students.student_number', 'classrooms.grade_level', 'classrooms.name']);
            $byStudent = $enrolments->keyBy('student_id');
            $rows = [];
            foreach ($members as $member) {
                $student = $member->student_id !== null;
                $enrolment = $student ? $byStudent->get($member->student_id) : null;
                $rows[] = [
                    'election_id' => $e->id, 'user_id' => $member->user_id,
                    'academy_member_id' => $member->id, 'member_code' => $member->member_code, 'display_name' => $member->user?->name ?: 'Member '.$member->id,
                    'voter_type' => $student ? 'student' : 'staff', 'grade_level' => $enrolment?->grade_level,
                    'classroom_name' => $enrolment?->name, 'student_number' => $enrolment?->student_number,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            if ($rows !== []) {
                ElectionVoter::upsert($rows, ['election_id', 'user_id'], [
                    'academy_member_id', 'member_code', 'display_name', 'voter_type', 'grade_level',
                    'classroom_name', 'student_number', 'updated_at',
                ]);
            }
        });
        $voters = ElectionVoter::where('election_id', $e->id);
        $counts = [
            'total' => (clone $voters)->count(),
            'students' => (clone $voters)->where('voter_type', 'student')->count(),
            'staff' => (clone $voters)->where('voter_type', 'staff')->count(),
            'without_member_code' => (clone $voters)->whereNull('member_code')->count(),
            'without_student_card' => (clone $voters)->where('voter_type', 'student')
                ->whereNotExists(function ($cards) use ($e) {
                    $cards->selectRaw('1')->from('student_cards')
                        ->join('academy_members as card_members', 'card_members.student_id', '=', 'student_cards.student_id')
                        ->whereColumn('card_members.id', 'election_voters.academy_member_id')
                        ->where('student_cards.academy_id', $e->academy_id)
                        ->where('student_cards.is_active_flag', 1);
                })->count(),
            'duplicate_member_rows' => $duplicateMemberRows,
            'skipped_no_user_account' => $skippedNoUserAccount,
            'skipped_inactive_student' => $skippedInactiveStudent,
        ];
        $e->update(['voter_roll_locked_at' => now()]);
        MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $actor->id, 'action' => MemberActivityLog::ACTION_ELECTION_VOTER_ROLL_LOCK, 'description' => 'ล็อกบัญชีผู้มีสิทธิ์เลือกตั้ง', 'new_values' => $counts]);

        return $counts;
    }
}
