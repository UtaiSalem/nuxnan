<?php

namespace App\Listeners\Enrollment;

use App\Events\Enrollment\RolloverCommitted;
use App\Services\EnrollmentNotificationService;

class SendRolloverCommittedNotification
{
    public function __construct(
        private readonly EnrollmentNotificationService $notifications,
    ) {}

    public function handle(RolloverCommitted $event): void
    {
        $this->notifications->notifyRolloverCommitted($event);
    }
}
