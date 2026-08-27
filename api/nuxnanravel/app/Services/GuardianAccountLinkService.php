<?php

namespace App\Services;

use App\Exceptions\GuardianAccountLinkException;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Guardian;
use App\Models\GuardianAccountRequest;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GuardianAccountLinkService
{
    private GuardianAuditLogger $logger;

    private GuardianAccessService $accessService;

    private NotificationService $notificationService;

    public function __construct(
        GuardianAuditLogger $logger,
        GuardianAccessService $accessService,
        NotificationService $notificationService
    ) {
        $this->logger = $logger;
        $this->accessService = $accessService;
        $this->notificationService = $notificationService;
    }

    public function createRequest(Academy $academy, Student $student, User $targetUser, User $actor, ?Guardian $guardian = null): GuardianAccountRequest
    {
        return DB::transaction(function () use ($academy, $student, $targetUser, $actor, $guardian) {
            if ($student->academy_id !== $academy->id) {
                throw GuardianAccountLinkException::invalid('นักเรียนไม่ได้อยู่ในสถาบันนี้');
            }
            if ($guardian !== null && $guardian->academy_id !== $academy->id) {
                throw GuardianAccountLinkException::invalid('ผู้ปกครองไม่ได้อยู่ในสถาบันนี้');
            }
            if ($guardian !== null && $guardian->user_id !== null && $guardian->user_id !== $targetUser->id) {
                throw GuardianAccountLinkException::conflict('แถวนี้มีเจ้าของบัญชีแล้ว');
            }

            $existingForUser = Guardian::where('academy_id', $academy->id)
                ->where('user_id', $targetUser->id)
                ->when($guardian, function ($query) use ($guardian) {
                    $query->where('id', '!=', $guardian->id);
                })
                ->exists();
            if ($existingForUser) {
                throw GuardianAccountLinkException::conflict('1 บัญชี = 1 คนต่อโรงเรียน');
            }

            $fullyLinked = Guardian::where('academy_id', $academy->id)
                ->where('user_id', $targetUser->id)
                ->whereHas('students', function ($query) use ($student) {
                    $query->where('students.id', $student->id);
                })
                ->exists();
            if ($fullyLinked) {
                throw GuardianAccountLinkException::conflict('ผูกแล้ว');
            }

            $pendingRequest = GuardianAccountRequest::where('student_id', $student->id)
                ->where('user_id', $targetUser->id)
                ->pending()
                ->lockForUpdate()
                ->first();
            if ($pendingRequest !== null) {
                throw GuardianAccountLinkException::conflict('มีคำขออยู่แล้ว');
            }

            if ($actor->id === $targetUser->id) {
                $initiatedByRole = GuardianAccountRequest::ROLE_GUARDIAN;
                $direction = GuardianAccountRequest::DIRECTION_STUDENT;
            } else {
                $initiatedByRole = $this->accessService->actorRole($actor, $student);
                $direction = GuardianAccountRequest::DIRECTION_GUARDIAN;
            }

            if ($direction === GuardianAccountRequest::DIRECTION_STUDENT && $student->user_id === null) {
                throw GuardianAccountLinkException::invalid('นักเรียนยังไม่มีบัญชีให้กดรับ');
            }

            $request = GuardianAccountRequest::create([
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'guardian_id' => $guardian?->id,
                'user_id' => $targetUser->id,
                'direction' => $direction,
                'initiated_by_user_id' => $actor->id,
                'initiated_by_role' => $initiatedByRole,
                'status' => GuardianAccountRequest::STATUS_PENDING,
            ]);

            $this->logger->accountRequested($student, $targetUser, $direction, $initiatedByRole);

            $receiverId = $direction === GuardianAccountRequest::DIRECTION_GUARDIAN
                ? $targetUser->id
                : $student->user_id;

            $this->notificationService->send([
                'user_id' => $receiverId,
                'content' => 'คำขอผูกบัญชีผู้ปกครอง',
                'type' => 'guardian_account_request',
                'related_id' => $request->id,
            ]);

            return $request;
        });
    }

    public function accept(GuardianAccountRequest $request, User $responder): GuardianAccountRequest
    {
        if (! $request->isPending()) {
            throw GuardianAccountLinkException::conflict('คำขอไม่ได้อยู่ในสถานะรอดำเนินการ');
        }

        if ($request->direction === GuardianAccountRequest::DIRECTION_GUARDIAN) {
            if ($responder->id !== $request->user_id) {
                throw GuardianAccountLinkException::forbidden('ผู้ตอบรับไม่ตรงกับผู้รับคำขอ');
            }
        } elseif ($request->direction === GuardianAccountRequest::DIRECTION_STUDENT) {
            if ($responder->id !== $request->student->user_id) {
                throw GuardianAccountLinkException::forbidden('ผู้ตอบรับไม่ตรงกับผู้รับคำขอ');
            }
        } else {
            throw GuardianAccountLinkException::forbidden('ทิศทางคำขอไม่ถูกต้อง');
        }

        return DB::transaction(function () use ($request, $responder) {
            $academyId = $request->academy_id;
            $targetUserId = $request->user_id;
            $guardian = $request->guardian;

            if ($guardian === null) {
                $targetUser = $request->user;
                $nameParts = explode(' ', trim($targetUser->name));
                $lastName = '';
                if (count($nameParts) > 1) {
                    $lastName = array_pop($nameParts);
                }
                $firstName = implode(' ', $nameParts);

                $guardian = Guardian::create([
                    'academy_id' => $academyId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'status' => 'alive',
                    'citizen_id' => null,
                    'monthly_income' => null,
                ]);
            }

            if ($guardian->user_id === null) {
                try {
                    $guardian->update(['user_id' => $targetUserId]);
                } catch (QueryException $e) {
                    throw GuardianAccountLinkException::conflict('บัญชีผู้ใช้นี้ถูกผูกกับผู้ปกครองอื่นในโรงเรียนนี้แล้ว');
                }
            } elseif ($guardian->user_id !== $targetUserId) {
                throw GuardianAccountLinkException::conflict('บัญชีผู้ใช้ไม่ตรงกับบัญชีที่มีอยู่');
            }

            StudentGuardianLink::firstOrCreate(
                [
                    'student_id' => $request->student_id,
                    'guardian_id' => $guardian->id,
                ],
                [
                    'appointed_by_user_id' => $request->initiated_by_user_id,
                    'appointed_by_role' => $request->initiated_by_role,
                    'appointed_at' => now(),
                ]
            );

            $academyMember = AcademyMember::where('academy_id', $academyId)
                ->where('user_id', $targetUserId)
                ->first();

            if (! $academyMember) {
                $roleParent = AcademyRole::where('name', 'parent')
                    ->where(function ($query) use ($academyId) {
                        $query->whereNull('academy_id')
                            ->orWhere('academy_id', $academyId);
                    })
                    ->orderByRaw('academy_id IS NULL')
                    ->first();

                AcademyMember::create([
                    'academy_id' => $academyId,
                    'user_id' => $targetUserId,
                    'role' => 'parent',
                    'academy_role_id' => $roleParent?->id,
                    'status' => AcademyMember::STATUS_APPROVED,
                ]);
            }

            $request->update([
                'status' => GuardianAccountRequest::STATUS_ACCEPTED,
                'responded_by_user_id' => $responder->id,
                'responded_at' => now(),
                'guardian_id' => $guardian->id, // If guardian was newly created, its id needs to be linked
            ]);

            $this->logger->accountLinked($request->student, $guardian, $request->user);

            $this->notificationService->send([
                'user_id' => $request->initiated_by_user_id,
                'content' => 'คำขอผูกบัญชีผู้ปกครองได้รับการตอบรับแล้ว',
                'type' => 'guardian_account_linked',
                'related_id' => $request->id,
            ]);

            return $request;
        });
    }

    public function decline(GuardianAccountRequest $request, User $responder, ?string $reason = null): void
    {
        if (! $request->isPending()) {
            throw GuardianAccountLinkException::conflict('คำขอไม่ได้อยู่ในสถานะรอดำเนินการ');
        }

        if ($request->direction === GuardianAccountRequest::DIRECTION_GUARDIAN) {
            if ($responder->id !== $request->user_id) {
                throw GuardianAccountLinkException::forbidden('ผู้ตอบรับไม่ตรงกับผู้รับคำขอ');
            }
        } elseif ($request->direction === GuardianAccountRequest::DIRECTION_STUDENT) {
            if ($responder->id !== $request->student->user_id) {
                throw GuardianAccountLinkException::forbidden('ผู้ตอบรับไม่ตรงกับผู้รับคำขอ');
            }
        } else {
            throw GuardianAccountLinkException::forbidden('ทิศทางคำขอไม่ถูกต้อง');
        }

        if ($reason !== null) {
            $reason = mb_substr($reason, 0, 255);
        }

        $request->update([
            'status' => GuardianAccountRequest::STATUS_DECLINED,
            'responded_by_user_id' => $responder->id,
            'responded_at' => now(),
            'decline_reason' => $reason,
        ]);

        $this->notificationService->send([
            'user_id' => $request->initiated_by_user_id,
            'content' => 'คำขอผูกบัญชีผู้ปกครองถูกปฏิเสธ',
            'type' => 'guardian_account_declined',
            'related_id' => $request->id,
        ]);
    }

    public function cancel(GuardianAccountRequest $request, User $actor): void
    {
        if (! $request->isPending()) {
            throw GuardianAccountLinkException::conflict('คำขอไม่ได้อยู่ในสถานะรอดำเนินการ');
        }

        $canCancel = $actor->id === $request->initiated_by_user_id
            || $this->accessService->allows($actor, $request->student, 'guardians.appoint');

        if (! $canCancel) {
            throw GuardianAccountLinkException::forbidden('ไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        $request->update([
            'status' => GuardianAccountRequest::STATUS_CANCELLED,
        ]);
    }

    public function unlink(Guardian $guardian, User $actor): void
    {
        $canUnlink = false;
        if ($actor->id === $guardian->user_id) {
            $canUnlink = true;
        } else {
            foreach ($guardian->students as $student) {
                if ($this->accessService->allows($actor, $student, 'guardians.manage')) {
                    $canUnlink = true;
                    break;
                }
            }
        }

        if (! $canUnlink) {
            throw GuardianAccountLinkException::forbidden('ไม่มีสิทธิ์ปลดผูกบัญชี');
        }

        if ($guardian->user_id === null) {
            return;
        }

        $account = $guardian->user;

        $guardian->update([
            'user_id' => null,
        ]);

        // Get the first student to log the action against
        $student = $guardian->students->first();
        if ($student && $account) {
            $this->logger->accountUnlinked($student, $guardian, $account);
        }

        if ($account) {
            $this->notificationService->send([
                'user_id' => $account->id,
                'content' => 'การผูกบัญชีผู้ปกครองถูกปลด',
                'type' => 'guardian_account_unlinked',
            ]);
        }
    }
}
