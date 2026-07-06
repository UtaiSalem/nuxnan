<?php

namespace App\Services;

use App\Enums\UsageEventType;
use App\Jobs\ProcessUsageEvent;
use App\Models\User;
use App\Models\UserUsageEvent;

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
        ProcessUsageEvent::dispatch($event);
    }

    /**
     * Generate a unique idempotency key for an event based on its strategy.
     */
    protected function generateIdempotencyKey(int $userId, string $eventType, ?int $sourceId): string
    {
        $strategy = UsageEventType::IDEMPOTENCY_STRATEGIES[$eventType] ?? 'daily';
        $sourceIdStr = $sourceId ?? 'none';

        $keyParts = match ($strategy) {
            'daily' => [$userId, $eventType, now()->toDateString()],
            'once_per_source' => [$userId, $eventType, $sourceIdStr],
            'daily_per_source' => [$userId, $eventType, $sourceIdStr, now()->toDateString()],
            'always' => [$userId, $eventType, $sourceIdStr, now()->format('Y-m-d H:i:s.u'), mt_rand()],
            default => [$userId, $eventType, now()->toDateString()],
        };

        return hash('sha256', implode('_', $keyParts));
    }
}
