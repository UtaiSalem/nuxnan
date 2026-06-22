<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentPromoted;
use App\Services\EnrollmentNotificationService;

class SendStudentPromotedNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentPromoted $event): void
    {
        $this->notifications->notifyStudentPromoted($event);
    }
}
