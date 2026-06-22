<?php

namespace App\Services;

use App\Events\Enrollment\RolloverCommitted;
use App\Events\Enrollment\RolloverUndone;
use App\Events\Enrollment\StudentDropped;
use App\Events\Enrollment\StudentEnrolled;
use App\Events\Enrollment\StudentGraduated;
use App\Events\Enrollment\StudentPromoted;
use App\Events\Enrollment\StudentRepeated;
use App\Events\Enrollment\StudentTransferred;
use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Support\Collection;

class EnrollmentNotificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function notifyStudentEnrolled(StudentEnrolled $event): int
    {
        $event->enrollment->loadMissing(['classroom.academy']);

        $student = $event->student;
        $classroom = $event->enrollment->classroom;
        $academy = $classroom?->academy;
        $senderId = $event->enrollment->created_by_user_id;

        return $this->sendToUsers(
            $this->studentAndTeacherRecipients($student, $classroom),
            Notification::TYPE_STUDENT_ENROLLED,
            "นักเรียน {$this->studentName($student)} ถูกลงทะเบียนเข้า {$this->classroomName($classroom)}",
            $senderId,
            $event->enrollment->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $academy?->id,
                'classroom_id' => $classroom?->id,
                'classroom_name' => $this->classroomName($classroom),
                'status' => $event->enrollment->status,
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyStudentTransferred(StudentTransferred $event): int
    {
        $event->closedFrom->loadMissing(['classroom.academy']);
        $event->opened->loadMissing(['classroom']);

        $student = $event->student;
        $fromClassroom = $event->closedFrom->classroom;
        $toClassroom = $event->opened->classroom;
        $senderId = $event->opened->created_by_user_id ?? $event->closedFrom->created_by_user_id;

        return $this->sendToUsers(
            $this->studentAndTeachersRecipients($student, [$fromClassroom, $toClassroom]),
            Notification::TYPE_STUDENT_TRANSFERRED,
            "นักเรียน {$this->studentName($student)} ย้ายห้องจาก {$this->classroomName($fromClassroom)} ไป {$this->classroomName($toClassroom)}",
            $senderId,
            $event->opened->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $event->opened->academy_id,
                'from_classroom_id' => $fromClassroom?->id,
                'from_classroom_name' => $this->classroomName($fromClassroom),
                'to_classroom_id' => $toClassroom?->id,
                'to_classroom_name' => $this->classroomName($toClassroom),
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyStudentPromoted(StudentPromoted $event): int
    {
        $event->closedFrom->loadMissing(['classroom.academy']);
        $event->opened->loadMissing(['classroom']);

        $student = $event->student;
        $fromClassroom = $event->closedFrom->classroom;
        $toClassroom = $event->opened->classroom;
        $academyId = $event->opened->academy_id ?: $event->closedFrom->academy_id;
        $senderId = $event->opened->created_by_user_id ?? $event->closedFrom->created_by_user_id;

        return $this->sendToUsers(
            $this->studentTeachersAndAdminsRecipients($student, [$fromClassroom, $toClassroom], $academyId),
            Notification::TYPE_STUDENT_PROMOTED,
            "นักเรียน {$this->studentName($student)} เลื่อนชั้นจาก {$this->classroomName($fromClassroom)} ไป {$this->classroomName($toClassroom)}",
            $senderId,
            $event->opened->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $academyId,
                'from_classroom_id' => $fromClassroom?->id,
                'from_classroom_name' => $this->classroomName($fromClassroom),
                'to_classroom_id' => $toClassroom?->id,
                'to_classroom_name' => $this->classroomName($toClassroom),
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyStudentRepeated(StudentRepeated $event): int
    {
        $event->closed->loadMissing(['classroom.academy']);
        $event->opened->loadMissing(['classroom']);

        $student = $event->student;
        $fromClassroom = $event->closed->classroom;
        $toClassroom = $event->opened->classroom;
        $academyId = $event->opened->academy_id ?: $event->closed->academy_id;
        $senderId = $event->opened->created_by_user_id ?? $event->closed->created_by_user_id;

        return $this->sendToUsers(
            $this->studentTeachersAndAdminsRecipients($student, [$fromClassroom, $toClassroom], $academyId),
            Notification::TYPE_STUDENT_REPEATED,
            "นักเรียน {$this->studentName($student)} ถูกย้ายซ้ำชั้นไป {$this->classroomName($toClassroom)}",
            $senderId,
            $event->opened->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $academyId,
                'from_classroom_id' => $fromClassroom?->id,
                'from_classroom_name' => $this->classroomName($fromClassroom),
                'to_classroom_id' => $toClassroom?->id,
                'to_classroom_name' => $this->classroomName($toClassroom),
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyStudentGraduated(StudentGraduated $event): int
    {
        $event->closed?->loadMissing(['classroom.academy']);

        $student = $event->student;
        $classroom = $event->closed?->classroom;
        $academyId = $event->closed?->academy_id ?: $student->academy_id;
        $senderId = $event->closed?->created_by_user_id;

        return $this->sendToUsers(
            $this->studentTeachersAndAdminsRecipients($student, [$classroom], $academyId),
            Notification::TYPE_STUDENT_GRADUATED,
            "นักเรียน {$this->studentName($student)} จบการศึกษาจาก {$this->classroomName($classroom)}",
            $senderId,
            $event->closed?->id ?? $student->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $academyId,
                'classroom_id' => $classroom?->id,
                'classroom_name' => $this->classroomName($classroom),
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyStudentDropped(StudentDropped $event): int
    {
        $event->closed?->loadMissing(['classroom.academy']);

        $student = $event->student;
        $classroom = $event->closed?->classroom;
        $academyId = $event->closed?->academy_id ?: $student->academy_id;
        $senderId = $event->closed?->created_by_user_id;

        return $this->sendToUsers(
            $this->studentTeachersAndAdminsRecipients($student, [$classroom], $academyId),
            Notification::TYPE_STUDENT_DROPPED,
            "นักเรียน {$this->studentName($student)} พ้นสภาพจาก {$this->classroomName($classroom)}",
            $senderId,
            $event->closed?->id ?? $student->id,
            [
                'student_id' => $student->id,
                'student_name' => $this->studentName($student),
                'academy_id' => $academyId,
                'classroom_id' => $classroom?->id,
                'classroom_name' => $this->classroomName($classroom),
                'reason' => $event->reason,
                'batch_id' => $event->batchId,
            ]
        );
    }

    public function notifyRolloverCommitted(RolloverCommitted $event): int
    {
        $event->batch->loadMissing(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy']);

        $batch = $event->batch;
        $academy = $batch->academy;

        return $this->sendToUsers(
            $this->academyAdminRecipientIds($academy),
            Notification::TYPE_ROLLOVER_COMMITTED,
            "มีการยืนยัน rollover ปีการศึกษา {$batch->fromAcademicYear?->name} ไป {$batch->toAcademicYear?->name} ใน {$academy?->name}",
            $batch->committed_by_user_id,
            null,
            [
                'batch_id' => $batch->id,
                'academy_id' => $academy?->id,
                'academy_name' => $academy?->name,
                'from_year_id' => $batch->from_academic_year_id,
                'from_year_name' => $batch->fromAcademicYear?->name,
                'to_year_id' => $batch->to_academic_year_id,
                'to_year_name' => $batch->toAcademicYear?->name,
                'totals' => $batch->totals,
            ]
        );
    }

    public function notifyRolloverUndone(RolloverUndone $event): int
    {
        $event->batch->loadMissing(['academy', 'fromAcademicYear', 'toAcademicYear', 'undoneBy']);

        $batch = $event->batch;
        $academy = $batch->academy;

        return $this->sendToUsers(
            $this->academyAdminRecipientIds($academy),
            Notification::TYPE_ROLLOVER_UNDONE,
            "มีการยกเลิก rollover ปีการศึกษา {$batch->fromAcademicYear?->name} ไป {$batch->toAcademicYear?->name} ใน {$academy?->name}",
            $batch->undone_by_user_id,
            null,
            [
                'batch_id' => $batch->id,
                'academy_id' => $academy?->id,
                'academy_name' => $academy?->name,
                'from_year_id' => $batch->from_academic_year_id,
                'from_year_name' => $batch->fromAcademicYear?->name,
                'to_year_id' => $batch->to_academic_year_id,
                'to_year_name' => $batch->toAcademicYear?->name,
                'totals' => $batch->totals,
            ]
        );
    }

    private function sendToUsers(
        Collection $userIds,
        string $type,
        string $content,
        ?int $senderId,
        ?int $relatedId,
        array $metadata
    ): int {
        $notifications = $userIds
            ->filter()
            ->unique()
            ->values()
            ->map(fn (int $userId) => [
                'user_id' => $userId,
                'type' => $type,
                'content' => $content,
                'sender_id' => $senderId,
                'related_id' => $relatedId,
                'action_url' => null,
                'metadata' => $metadata,
            ])
            ->all();

        return $this->notifications->sendBulk($notifications);
    }

    private function studentAndTeacherRecipients(Student $student, ?Classroom $classroom): Collection
    {
        return collect([$student->user_id, $classroom?->homeroom_teacher_id])->filter();
    }

    /**
     * @param  array<int, Classroom|null>  $classrooms
     */
    private function studentAndTeachersRecipients(Student $student, array $classrooms): Collection
    {
        return collect([$student->user_id])
            ->merge(
                collect($classrooms)
                    ->filter()
                    ->pluck('homeroom_teacher_id')
            )
            ->filter();
    }

    /**
     * @param  array<int, Classroom|null>  $classrooms
     */
    private function studentTeachersAndAdminsRecipients(Student $student, array $classrooms, ?int $academyId): Collection
    {
        return $this->studentAndTeachersRecipients($student, $classrooms)
            ->merge($this->academyAdminRecipientIds($academyId))
            ->filter();
    }

    private function academyAdminRecipientIds(Academy|int|null $academy): Collection
    {
        if ($academy instanceof Academy) {
            $academyModel = $academy;
        } elseif ($academy) {
            $academyModel = Academy::find($academy);
        } else {
            $academyModel = null;
        }

        if (! $academyModel) {
            return collect();
        }

        return collect([$academyModel->user_id, $academyModel->owner_id])
            ->merge(
                AcademyAdmin::query()
                    ->where('academy_id', $academyModel->id)
                    ->pluck('user_id')
            )
            ->filter()
            ->unique()
            ->values();
    }

    private function studentName(Student $student): string
    {
        $thaiName = trim(($student->first_name_th ?? '').' '.($student->last_name_th ?? ''));
        if ($thaiName !== '') {
            return $thaiName;
        }

        $englishName = trim(($student->first_name_en ?? '').' '.($student->last_name_en ?? ''));

        return $englishName !== '' ? $englishName : "Student #{$student->id}";
    }

    private function classroomName(?Classroom $classroom): string
    {
        return $classroom?->display_name ?? 'ไม่ระบุห้องเรียน';
    }
}
