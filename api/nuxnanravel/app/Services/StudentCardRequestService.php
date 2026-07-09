<?php

namespace App\Services;

use App\Enums\StudentCardRequestOrigin;
use App\Enums\StudentCardRequestStatus;
use App\Enums\StudentCardRequestType;
use App\Models\Academy;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentCardRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentCardRequestService
{
    private const TRANSITIONS = [
        'pending' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['in_progress'],
        'in_progress' => ['completed'],
    ];

    public function create(Academy $academy, Student $student, User $actor, array $data): StudentCardRequest
    {
        return $this->buildRequest($academy, $student, $actor, StudentCardRequestOrigin::Teacher, $data);
    }

    public function createPublic(Academy $academy, Student $student, array $data): StudentCardRequest
    {
        return $this->buildRequest($academy, $student, null, StudentCardRequestOrigin::Public, $data);
    }

    private function buildRequest(Academy $academy, Student $student, ?User $actor, StudentCardRequestOrigin $origin, array $data): StudentCardRequest
    {
        if ((int) $student->academy_id !== (int) $academy->id) {
            throw ValidationException::withMessages(['student_id' => 'Student does not belong to this academy.']);
        }

        $enrollment = ClassroomStudent::query()
            ->with('classroom')
            ->where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->when($data['classroom_id'] ?? null, fn ($query, $id) => $query->where('classroom_id', $id))
            ->latest('id')
            ->first();

        if (! $enrollment || ! $enrollment->classroom) {
            throw ValidationException::withMessages(['student_id' => 'Student has no active classroom enrollment.']);
        }

        if (StudentCardRequest::query()->where('academy_id', $academy->id)->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved', 'in_progress'])->exists()) {
            throw ValidationException::withMessages(['student_id' => 'Student already has an open card request.']);
        }

        $existingCard = StudentCard::query()
            ->where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->where('student_status', 'active')
            ->latest('id')
            ->first();
        $type = StudentCardRequestType::from($data['request_type']);

        if ($type !== StudentCardRequestType::FirstIssue && ! $existingCard) {
            throw ValidationException::withMessages(['request_type' => 'Replacement and renewal require an active card.']);
        }
        if ($type === StudentCardRequestType::FirstIssue && $existingCard) {
            throw ValidationException::withMessages(['request_type' => 'Student already has an active card.']);
        }

        return StudentCardRequest::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'classroom_id' => $enrollment->classroom_id,
            'student_id' => $student->id,
            'existing_card_id' => $existingCard?->id,
            'request_type' => $type,
            'status' => StudentCardRequestStatus::Pending,
            'priority' => $data['priority'] ?? 'normal',
            'origin' => $origin,
            'full_name_snapshot' => $student->full_name_th,
            'student_number_snapshot' => $student->student_id,
            'grade_level_snapshot' => $enrollment->classroom->grade_level,
            'section_snapshot' => $enrollment->classroom->section,
            'reason' => $data['reason'] ?? null,
            'requested_by' => $actor?->id,
            'requested_at' => now(),
        ]);
    }

    public function transition(StudentCardRequest $request, StudentCardRequestStatus $to, User $actor, array $context = []): StudentCardRequest
    {
        return DB::transaction(function () use ($request, $to, $actor, $context) {
            $locked = StudentCardRequest::query()->lockForUpdate()->findOrFail($request->id);
            $from = $locked->status;
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Invalid transition from {$from->value} to {$to->value}."]);
            }

            $changes = ['status' => $to, 'admin_notes' => $context['admin_notes'] ?? $locked->admin_notes];
            match ($to) {
                StudentCardRequestStatus::Approved => $changes += ['approved_by' => $actor->id, 'approved_at' => now()],
                StudentCardRequestStatus::Rejected => $changes += ['processed_by' => $actor->id, 'rejection_reason' => $context['rejection_reason'], 'rejected_at' => now()],
                StudentCardRequestStatus::InProgress => $changes += ['processed_by' => $actor->id, 'started_at' => now()],
                StudentCardRequestStatus::Cancelled => $changes += ['cancelled_at' => now()],
                default => null,
            };
            $locked->update($changes);

            return $locked->fresh();
        });
    }

    public function complete(StudentCardRequest $request, User $actor, array $context = []): StudentCardRequest
    {
        return DB::transaction(function () use ($request, $actor, $context) {
            $locked = StudentCardRequest::query()->with('student')->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== StudentCardRequestStatus::InProgress || ! $locked->student) {
                throw ValidationException::withMessages(['status' => 'Only an in-progress request with an active student can be completed.']);
            }

            StudentCard::query()
                ->where('academy_id', $locked->academy_id)
                ->where('student_id', $locked->student_id)
                ->where('student_status', 'active')
                ->lockForUpdate()
                ->update(['student_status' => 'expired']);

            $student = $locked->student;
            $issueDate = Carbon::parse($context['card_issue_date'] ?? now());
            $card = StudentCard::create([
                'student_id' => $student->id,
                'academy_id' => $locked->academy_id,
                'academic_year_id' => $locked->academic_year_id,
                'student_number' => $student->student_id,
                'full_name_thai' => $locked->full_name_snapshot,
                'title_name' => $student->title_prefix_th,
                'first_name_thai' => $student->first_name_th,
                'last_name_thai' => $student->last_name_th,
                'first_name_english' => $student->first_name_en,
                'national_id' => $student->citizen_id,
                'birth_date' => $student->date_of_birth,
                'birth_date_string' => $student->date_of_birth?->format('d/m/Y'),
                'class_level' => preg_replace('/\D+/u', '', (string) $locked->grade_level_snapshot) ?: null,
                'class_section' => (int) $locked->section_snapshot ?: null,
                'level_and_room' => trim($locked->grade_level_snapshot.'/'.$locked->section_snapshot, '/'),
                'card_issue_date' => $issueDate,
                'card_expiry_date' => $context['card_expiry_date'] ?? $issueDate->copy()->addYears(3),
                'student_status' => 'active',
                'profile_image' => $student->profile_image,
            ]);

            $locked->update([
                'status' => StudentCardRequestStatus::Completed,
                'result_card_id' => $card->id,
                'processed_by' => $actor->id,
                'admin_notes' => $context['admin_notes'] ?? $locked->admin_notes,
                'completed_at' => now(),
            ]);

            return $locked->fresh(['resultCard']);
        });
    }
}
