<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentDropped;
use App\Services\EnrollmentNotificationService;

class SendStudentDroppedNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentDropped $event): void
    {
        $this->notifications->notifyStudentDropped($event);
    }
}
