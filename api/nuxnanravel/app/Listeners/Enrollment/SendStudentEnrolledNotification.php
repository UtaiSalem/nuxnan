<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentEnrolled;
use App\Services\EnrollmentNotificationService;

class SendStudentEnrolledNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentEnrolled $event): void
    {
        $this->notifications->notifyStudentEnrolled($event);
    }
}
