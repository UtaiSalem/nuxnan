<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUsageEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsageEventService
{
    /**
     * Record a new usage event.
     */
    public function record(User $user, string $eventType, ?string $sourceType = null, ?int $sourceId = null, array $context = []): UserUsageEvent
    {
        $idempotencyKey = $this->generateIdempotencyKey($user->id, $eventType, $sourceId);

        return UserUsageEvent::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'context' => $context,
                'occurred_at' => now(),
                'ip_hash' => hash('sha256', request()->ip() ?? '127.0.0.1'),
            ]
        );
    }

    /**
     * Facade-style static method to fire an event.
     */
    public static function fire(User $user, string $eventType, ?string $sourceType = null, ?int $sourceId = null, array $context = []): void
    {
        $service = app(self::class);
        $event = $service->record($user, $eventType, $sourceType, $sourceId, $context);

        // Dispatch job to process the event
        \App\Jobs\ProcessUsageEvent::dispatch($event);
    }

    /**
     * Generate a unique idempotency key for an event.
     * For daily limited events, we include the date.
     */
    protected function generateIdempotencyKey(int $userId, string $eventType, ?int $sourceId): string
    {
        $date = now()->toDateString();
        $sourceIdStr = $sourceId ?? 'none';
        return hash('sha256', "{$userId}_{$eventType}_{$sourceIdStr}_{$date}");
    }
}
