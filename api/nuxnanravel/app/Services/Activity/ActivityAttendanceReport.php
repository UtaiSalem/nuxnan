<?php

namespace App\Services\Activity;

use App\Models\ActivityAttendance;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;

class ActivityAttendanceReport
{
    public function build(SchoolEvent $event, ?string $from = null, ?string $to = null): array
    {
        $sessionQuery = ActivitySession::where('event_id', $event->id)
            ->when($from, fn ($query) => $query->whereDate('start_datetime', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('start_datetime', '<=', $to));
        $sessionIds = $sessionQuery->pluck('id');
        $sessionsTotal = $sessionIds->count();

        $attendanceCounts = ActivityAttendance::whereIn('session_id', $sessionIds)
            ->groupBy('user_id', 'status')
            ->selectRaw('user_id, status, COUNT(*) as c')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('c', 'status')->all());

        $rows = [];
        foreach (app(EventAudienceResolver::class)->rosterRows($event) as $rosterRow) {
            $counts = $attendanceCounts->get($rosterRow->user_id, []);
            $present = (int) ($counts['present'] ?? 0);
            $late = (int) ($counts['late'] ?? 0);
            $leave = (int) ($counts['leave'] ?? 0);
            $activityLeave = (int) ($counts['activity_leave'] ?? 0);
            $absent = (int) ($counts['absent'] ?? 0);
            $recorded = $present + $late + $leave + $activityLeave + $absent;

            $rows[] = [
                'user_id' => (int) $rosterRow->user_id,
                'name' => (string) $rosterRow->name,
                'student_number' => $rosterRow->student_number,
                'classroom_name' => $rosterRow->classroom_name,
                'present' => $present,
                'late' => $late,
                'leave' => $leave,
                'activity_leave' => $activityLeave,
                'absent' => $absent,
                'not_recorded' => max(0, $sessionsTotal - $recorded),
                'attendance_rate' => $sessionsTotal > 0
                    ? round((($present + $late) / $sessionsTotal) * 100, 1)
                    : 0.0,
            ];
        }

        return ['sessions_total' => $sessionsTotal, 'rows' => $rows];
    }
}
