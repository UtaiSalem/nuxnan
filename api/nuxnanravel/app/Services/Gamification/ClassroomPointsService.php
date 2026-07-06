<?php

namespace App\Services\Gamification;

use App\Models\AcademyGroup;
use App\Models\ClassroomPointCycle;
use App\Models\XpEvent;
use Carbon\Carbon;

class ClassroomPointsService
{
    use HasGamificationCycles;

    public function award(
        AcademyGroup $classroom,
        string $source,
        int $points,
        ?int $userId = null,
        array $metadata = []
    ): XpEvent {
        $event = XpEvent::create([
            'academy_id' => $classroom->academy_id,
            'user_id' => $userId,
            'classroom_group_id' => $classroom->id,
            'source' => $source,
            'xp' => 0,
            'classroom_pts' => $points,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        foreach ($this->activeCycles() as $cycle) {
            $row = ClassroomPointCycle::firstOrCreate(
                [
                    'classroom_group_id' => $classroom->id,
                    'cycle_type' => $cycle['type'],
                    'cycle_key' => $cycle['key'],
                ],
                [
                    'academy_id' => $classroom->academy_id,
                    'cycle_start' => $cycle['start'],
                    'cycle_end' => $cycle['end'],
                    'total_points' => 0,
                ]
            );
            $row->increment('total_points', $points);
        }

        return $event;
    }

    /**
     * Pre-create cycle rows for the scheduler
     */
    public function ensureCurrentCycles(AcademyGroup $classroom): void
    {
        foreach ($this->activeCycles() as $cycle) {
            ClassroomPointCycle::firstOrCreate(
                [
                    'classroom_group_id' => $classroom->id,
                    'cycle_type' => $cycle['type'],
                    'cycle_key' => $cycle['key'],
                ],
                [
                    'academy_id' => $classroom->academy_id,
                    'cycle_start' => $cycle['start'],
                    'cycle_end' => $cycle['end'],
                    'total_points' => 0,
                ]
            );
        }
    }

    /**
     * Top N classrooms in academy by points for given cycle
     */
    public function leaderboard(int $academyId, string $cycleType = 'month', int $limit = 3): array
    {
        $key = match ($cycleType) {
            'week' => Carbon::now()->format('o-\WW'),
            'month' => Carbon::now()->format('Y-m'),
            default => 'all',
        };

        return ClassroomPointCycle::with('classroomGroup:id,name,type')
            ->where('academy_id', $academyId)
            ->where('cycle_type', $cycleType)
            ->where('cycle_key', $key)
            ->whereHas('classroomGroup', fn ($q) => $q->where('type', 'classroom'))
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'group_id' => $r->classroom_group_id,
                'name' => $r->classroomGroup?->name ?? 'ห้องเรียนทั่วไป',
                'points' => $r->total_points,
            ])
            ->all();
    }
}
