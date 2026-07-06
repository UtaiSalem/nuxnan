<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Learn\Academy\BehaviorRecord;
use App\Models\Learn\Academy\BehaviorScore;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\Gamification\XpService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BehaviorService
{
    public function __construct(private XpService $xpService) {}

    public function recordBehavior(Academy $academy, Student $student, array $data): BehaviorRecord
    {
        return DB::transaction(function () use ($academy, $student, $data) {
            $currentYear = AcademicYear::where('academy_id', $academy->id)
                ->where('is_current', true)->first();

            $currentSemester = Semester::where('academic_year_id', $currentYear?->id)
                ->where('is_current', true)->first();

            $recordData = array_merge($data, [
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'academic_year_id' => $currentYear?->id,
                'semester_id' => $currentSemester?->id,
                'classroom_id' => $student->currentEnrollment?->classroom_id,
                'incident_date' => $data['incident_date'] ?? Carbon::today(),
                'status' => 'recorded', // Default status
            ]);

            // Logic for pending approval
            if (isset($data['severity']) && isset($data['type'])) {
                $severity = strtolower($data['severity']);
                if (in_array($severity, ['high', 'critical']) && $data['type'] === 'negative') {
                    $recordData['status'] = 'pending_approval';
                }
            }

            $record = BehaviorRecord::create($recordData);

            if ($record->status !== 'pending_approval') {
                $this->applyPoints($record);
            }

            return $record;
        });
    }

    public function applyPoints(BehaviorRecord $record): void
    {
        $score = BehaviorScore::firstOrCreate(
            [
                'student_id' => $record->student_id,
                'academy_id' => $record->academy_id,
                'academic_year_id' => $record->academic_year_id,
                'semester_id' => $record->semester_id,
            ],
            [
                'positive_points' => 0,
                'negative_points' => 0,
                'net_score' => 0,
                'record_count' => 0,
            ]
        );

        if ($record->type === 'positive') {
            $score->increment('positive_points', $record->points);
            $score->increment('net_score', $record->points);
        } elseif ($record->type === 'negative') {
            $score->increment('negative_points', $record->points);
            $score->decrement('net_score', $record->points);
        }

        $score->increment('record_count');
        $score->update(['last_incident_at' => $record->incident_date]);

        // Award XP only for positive
        if ($record->type === 'positive' && $record->points > 0) {
            $student = $record->student;
            $academy = $record->academy;

            if ($student && $academy) {
                $this->xpService->award(
                    academy: $academy,
                    source: 'behavior.positive',
                    xp: $record->points,
                    userId: $student->user_id,
                    classroomGroupId: null, // optional
                    metadata: ['record_id' => $record->id, 'title' => $record->title ?? 'Behavior Positive Point']
                );
            }
        }
    }

    public function approveRecord(BehaviorRecord $record, User $approver): BehaviorRecord
    {
        if ($record->status !== 'pending_approval') {
            throw new Exception('Record is not pending approval.');
        }

        return DB::transaction(function () use ($record, $approver) {
            $record->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->applyPoints($record);

            return $record;
        });
    }

    public function rejectRecord(BehaviorRecord $record, User $approver, string $reason): BehaviorRecord
    {
        if ($record->status !== 'pending_approval') {
            throw new Exception('Record is not pending approval.');
        }

        $record->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $record;
    }

    public function recalculateScore(Academy $academy, Student $student, int $academicYearId, ?int $semesterId = null): BehaviorScore
    {
        return DB::transaction(function () use ($academy, $student, $academicYearId, $semesterId) {
            $query = BehaviorRecord::where('academy_id', $academy->id)
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->whereIn('status', ['recorded', 'approved']);

            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }

            $records = $query->get();

            $positivePoints = $records->where('type', 'positive')->sum('points');
            $negativePoints = $records->where('type', 'negative')->sum('points');
            $recordCount = $records->count();
            $lastIncidentAt = $records->max('incident_date');

            $score = BehaviorScore::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academy_id' => $academy->id,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId,
                ],
                [
                    'positive_points' => $positivePoints,
                    'negative_points' => $negativePoints,
                    'net_score' => $positivePoints - $negativePoints,
                    'record_count' => $recordCount,
                    'last_incident_at' => $lastIncidentAt,
                ]
            );

            return $score;
        });
    }
}
