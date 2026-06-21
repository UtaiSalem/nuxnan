<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentRepeated;
use App\Services\EnrollmentNotificationService;

class SendStudentRepeatedNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentRepeated $event): void
    {
        $this->notifications->notifyStudentRepeated($event);
    }
}
