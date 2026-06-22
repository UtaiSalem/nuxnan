<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\RolloverUndone;
use App\Services\EnrollmentNotificationService;

class SendRolloverUndoneNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(RolloverUndone $event): void
    {
        $this->notifications->notifyRolloverUndone($event);
    }
}
