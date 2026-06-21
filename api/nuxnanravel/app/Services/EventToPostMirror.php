<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\SchoolEvent;

class EventToPostMirror
{
    public function mirror(SchoolEvent $event): AcademyPost
    {
        $post = AcademyPost::where('academy_id', $event->academy_id)
            ->where('embed_data->event_id', $event->id)
            ->first();

        $postData = [
            'user_id' => $event->created_by,
            'content' => $event->description ?? $event->title,
            'post_type' => 'event',
            'target_audience' => $event->target_audience,
            'embed_data' => [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'event_date' => $event->start_datetime?->toIso8601String(),
                'event_end' => $event->end_datetime?->toIso8601String(),
                'location' => $event->location,
                'event_type' => $event->event_type,
                'requires_register' => $event->requires_registration ?? false,
            ],
        ];

        if ($post) {
            $post->update($postData);
        } else {
            $post = AcademyPost::create(array_merge([
                'academy_id' => $event->academy_id,
            ], $postData));
        }

        // Wire Activity polymorphic for feed
        Activity::firstOrCreate(
            [
                'activityable_type' => AcademyPost::class,
                'activityable_id' => $post->id,
            ],
            [
                'user_id' => $post->user_id,
                'activity_type' => ActivityType::CREATE_POST->value,
            ]
        );

        return $post;
    }

    public function unmirror(SchoolEvent $event): void
    {
        $posts = AcademyPost::where('academy_id', $event->academy_id)
            ->where('embed_data->event_id', $event->id)
            ->get();

        foreach ($posts as $post) {
            Activity::where('activityable_type', AcademyPost::class)
                ->where('activityable_id', $post->id)
                ->delete();

            $post->images()->delete();
            $post->comments()->delete();
            $post->delete();
        }
    }
}
