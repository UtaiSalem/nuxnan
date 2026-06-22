<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\StudentGraduated;
use App\Services\EnrollmentNotificationService;

class SendStudentGraduatedNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(StudentGraduated $event): void
    {
        $this->notifications->notifyStudentGraduated($event);
    }
}
