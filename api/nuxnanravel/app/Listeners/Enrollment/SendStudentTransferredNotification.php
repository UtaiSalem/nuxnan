<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentTransferred;
use App\Services\EnrollmentNotificationService;

class SendStudentTransferredNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentTransferred $event): void
    {
        $this->notifications->notifyStudentTransferred($event);
    }
}
