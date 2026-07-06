<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{
    public function getUserEvents(User $user)
    {
        $events = Event::where('user_id', $user->id)
            ->orWhere(function ($q) {
                // If we had a participation system, we'd check it here
                // For now, simple hosted events
            })
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => (string) $event->id,
                    'start_date' => $event->start_time->toIsoString(),
                    'end_date' => $event->end_time ? $event->end_time->toIsoString() : null,
                    'location' => $event->location,
                    'cover_image' => $event->cover_image,
                    'attendees_count' => 0, // Placeholder
                    'is_going' => false, // Placeholder
                    'category' => 'General', // Placeholder
                ];
            }),
        ]);
    }
}
