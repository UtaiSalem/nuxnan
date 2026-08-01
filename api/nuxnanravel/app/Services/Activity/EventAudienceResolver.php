<?php

namespace App\Services\Activity;

use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventAudienceResolver
{
    public function resolve(SchoolEvent $event): Collection
    {
        return $this->audienceQuery($event)->pluck('academy_members.user_id')->unique()->values();
    }

    public function count(SchoolEvent $event): int
    {
        return $this->audienceQuery($event)->distinct('academy_members.user_id')->count('academy_members.user_id');
    }

    public function includes(SchoolEvent $event, int $userId): bool
    {
        return $this->audienceQuery($event)->where('academy_members.user_id', $userId)->exists();
    }

    // Approved membership of the event's academy, ignoring target_audience entirely.
    public function isMember(SchoolEvent $event, int $userId): bool
    {
        return DB::table('academy_members')
            ->where('academy_members.academy_id', $event->academy_id)
            ->where('academy_members.status', 2)
            ->whereNotNull('academy_members.user_id')
            ->where('academy_members.user_id', $userId)
            ->exists();
    }

    public function roster(SchoolEvent $event, ?ActivitySession $session = null): LengthAwarePaginator
    {
        $query = $this->audienceQuery($event)
            ->leftJoin('users', 'users.id', '=', 'academy_members.user_id')
            ->leftJoin('classroom_students', function ($join) {
                $join->on('classroom_students.student_id', '=', 'academy_members.student_id')
                    ->where('classroom_students.status', 'active');
            })
            ->leftJoin('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
            ->select([
                'academy_members.user_id', 'users.name', 'classroom_students.student_number',
                'classrooms.name as classroom_name',
            ]);

        if ($session) {
            $query->leftJoin('activity_attendances', function ($join) use ($session) {
                $join->on('activity_attendances.user_id', '=', 'academy_members.user_id')
                    ->where('activity_attendances.session_id', $session->id);
            })->addSelect('activity_attendances.status as attendance_status');
        }

        if (request()->filled('q')) {
            $query->where('users.name', 'like', '%'.request('q').'%');
        }

        return $query->distinct()->orderBy('users.name')->paginate(request()->integer('per_page', 25));
    }

    public function rosterRows(SchoolEvent $event): Collection
    {
        return $this->audienceQuery($event)
            ->leftJoin('users', 'users.id', '=', 'academy_members.user_id')
            ->leftJoin('classroom_students', function ($join) {
                $join->on('classroom_students.student_id', '=', 'academy_members.student_id')
                    ->where('classroom_students.status', 'active');
            })
            ->leftJoin('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
            ->select([
                'academy_members.user_id', 'users.name', 'classroom_students.student_number',
                'classrooms.name as classroom_name',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function audienceQuery(SchoolEvent $event): Builder
    {
        $audience = $event->target_audience;
        $base = DB::table('academy_members')
            ->where('academy_members.academy_id', $event->academy_id)
            ->where('academy_members.status', 2)
            ->whereNotNull('academy_members.user_id');

        if (! is_array($audience) || $audience === []) {
            return $base;
        }
        if (($audience['all'] ?? false) === true) {
            return $base->whereNotIn('academy_members.user_id', $audience['exclude_user_ids'] ?? []);
        }

        $base->where(function ($query) use ($audience) {
            $roles = $audience['roles'] ?? [];
            if (in_array('student', $roles, true)) {
                $query->orWhereNotNull('academy_members.student_id');
            }
            if (in_array('staff', $roles, true)) {
                $query->orWhereNull('academy_members.student_id');
            }
            if (! empty($audience['user_ids'])) {
                $query->orWhereIn('academy_members.user_id', $audience['user_ids']);
            }
            if (! empty($audience['classroom_ids'])) {
                $query->orWhereIn('academy_members.student_id', function ($q) use ($audience) {
                    $q->from('classroom_students')->whereIn('classroom_id', $audience['classroom_ids'])->where('status', 'active')->select('student_id');
                });
            }
            if (! empty($audience['grade_levels'])) {
                $query->orWhereIn('academy_members.student_id', function ($q) use ($audience) {
                    $q->from('classroom_students')->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')->whereIn('classrooms.grade_level', $audience['grade_levels'])->where('classroom_students.status', 'active')->select('student_id');
                });
            }
            if (! empty($audience['education_levels'])) {
                $query->orWhereIn('academy_members.student_id', function ($q) use ($audience) {
                    $q->from('student_academic_info')->whereIn('education_level', $audience['education_levels'])->where('is_current', true)->select('student_id');
                });
            }
            if (! empty($audience['group_ids'])) {
                $query->orWhereIn('academy_members.user_id', function ($q) use ($audience) {
                    $q->from('academy_group_members')->whereIn('academy_group_id', $audience['group_ids'])->select('user_id');
                });
            }
        });

        return $base->whereNotIn('academy_members.user_id', $audience['exclude_user_ids'] ?? []);
    }
}
