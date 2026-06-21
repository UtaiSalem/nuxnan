<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a single notification
     *
     * @param array $data
     * @return Notification
     */
    public function send(array $data): Notification
    {
        return Notification::create([
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'read_status' => $data['read_status'] ?? false,
            'action_url' => $data['action_url'] ?? null,
            'type' => $data['type'],
            'sender_id' => $data['sender_id'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Send bulk notifications
     *
     * @param array $notifications Array of arrays containing notification fields
     * @return int Number of notifications sent
     */
    public function sendBulk(array $notifications): int
    {
        if (empty($notifications)) {
            return 0;
        }

        $now = now();
        $records = [];

        foreach ($notifications as $notification) {
            $metadata = $notification['metadata'] ?? null;
            if (is_array($metadata) || is_object($metadata)) {
                $metadata = json_encode($metadata);
            }

            $records[] = [
                'user_id' => $notification['user_id'],
                'content' => $notification['content'],
                'read_status' => $notification['read_status'] ?? false,
                'action_url' => $notification['action_url'] ?? null,
                'type' => $notification['type'],
                'sender_id' => $notification['sender_id'] ?? null,
                'related_id' => $notification['related_id'] ?? null,
                'metadata' => $metadata,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Notification::insert($records);

        return count($records);
    }
}
