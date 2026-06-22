<?php

namespace App\Observers;

use App\Models\EventRegistration;
use App\Services\Gamification\XpService;

class EventRegistrationObserver
{
    public function __construct(protected XpService $xpService) {}

    public function updated(EventRegistration $registration): void
    {
        if ($registration->isDirty('status') && $registration->status === 'attended') {
            $event = $registration->event;
            if (!$event) {
                return;
            }

            $event->loadMissing('academy');
            if ($event->academy) {
                $this->xpService->award(
                    $event->academy,
                    'event.attended',
                    config('xp_rates.school.event.attended', 20),
                    $registration->user_id,
                    null,
                    [
                        'event_id' => $event->id,
                        'title'    => $event->title,
                    ]
                );
            }
        }
    }
}
