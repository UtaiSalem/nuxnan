<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('race.{roomCode}', function ($user, $roomCode) {
    $room = \App\Models\TypingRaceRoom::where('room_code', $roomCode)->first();
    if (!$room) return false;

    // Check if user is a participant in this room
    $isParticipant = $room->participants()->where('user_id', $user->id)->exists();
    if (!$isParticipant) return false;

    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->profile_photo_url ?? $user->avatar,
    ];
});
