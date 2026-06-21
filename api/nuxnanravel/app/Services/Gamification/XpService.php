<?php

namespace App\Services\Gamification;

use App\Models\Academy;
use App\Models\SchoolXpCycle;
use App\Models\XpEvent;
use Carbon\Carbon;

class XpService
{
    use HasGamificationCycles;

    /**
     * Award XP for an action. Logs xp_events + increments active cycles.
     */
    public function award(
        Academy $academy,
        string $source,
        int $xp,
        ?int $userId = null,
        ?int $classroomGroupId = null,
        array $metadata = []
    ): XpEvent {
        // 1. Log immutable event
        $event = XpEvent::create([
            'academy_id'         => $academy->id,
            'user_id'            => $userId,
            'classroom_group_id' => $classroomGroupId,
            'source'             => $source,
            'xp'                 => $xp,
            'classroom_pts'      => 0,
            'metadata'           => $metadata,
            'occurred_at'        => now(),
        ]);

        // 2. Update aggregates for active cycles (week, month, all_time)
        foreach ($this->activeCycles() as $cycle) {
            $row = SchoolXpCycle::firstOrCreate(
                [
                    'academy_id' => $academy->id,
                    'cycle_type' => $cycle['type'],
                    'cycle_key'  => $cycle['key'],
                ],
                [
                    'cycle_start' => $cycle['start'],
                    'cycle_end'   => $cycle['end'],
                    'total_xp'    => 0,
                    'level'       => 1,
                ]
            );
            
            // Increment total_xp atomically
            $row->increment('total_xp', $xp);
            
            // Recalculate level based on updated total_xp
            $row->level = $this->levelFromXp($row->total_xp);
            $row->save();
        }

        return $event;
    }

    public function levelFromXp(int $xp): int
    {
        return max(1, (int) floor(sqrt($xp / 1000)));
    }

    public function xpToNextLevel(int $xp): int
    {
        $level = $this->levelFromXp($xp);
        return ($level + 1) ** 2 * 1000;
    }

    /**
     * Pre-create cycle rows for the scheduler
     */
    public function ensureCurrentCycles(Academy $academy): void
    {
        foreach ($this->activeCycles() as $cycle) {
            SchoolXpCycle::firstOrCreate(
                [
                    'academy_id' => $academy->id,
                    'cycle_type' => $cycle['type'],
                    'cycle_key'  => $cycle['key'],
                ],
                [
                    'cycle_start' => $cycle['start'],
                    'cycle_end'   => $cycle['end'],
                    'total_xp'    => 0,
                    'level'       => 1,
                ]
            );
        }
    }

    public function summary(Academy $academy, string $cycleType = 'all_time'): array
    {
        $key = match ($cycleType) {
            'week'  => Carbon::now()->format('o-\WW'),
            'month' => Carbon::now()->format('Y-m'),
            default => 'all',
        };

        $row = SchoolXpCycle::where('academy_id', $academy->id)
            ->where('cycle_type', $cycleType)
            ->where('cycle_key', $key)
            ->first();

        $total = $row?->total_xp ?? 0;
        $level = $this->levelFromXp($total);
        $next  = $this->xpToNextLevel($total);
        $prev  = $level ** 2 * 1000;
        $pct   = $next > $prev ? (int) (100 * ($total - $prev) / ($next - $prev)) : 0;

        return [
            'cycle_type'   => $cycleType,
            'cycle_key'    => $key,
            'total_xp'     => $total,
            'level'        => $level,
            'next_level'   => $level + 1,
            'xp_to_next'   => max(0, $next - $total),
            'progress_pct' => max(0, min(100, $pct)),
        ];
    }
}
