<?php

namespace App\Services\Gamification;

use Carbon\Carbon;

trait HasGamificationCycles
{
    /**
     * Compute current cycles (week, month, all_time)
     */
    protected function activeCycles(): array
    {
        $now = Carbon::now();
        return [
            [
                'type'  => 'week',
                'key'   => $now->format('o-\WW'), // ISO year-week e.g. '2026-W26'
                'start' => $now->copy()->startOfWeek(),
                'end'   => $now->copy()->endOfWeek(),
            ],
            [
                'type'  => 'month',
                'key'   => $now->format('Y-m'),
                'start' => $now->copy()->startOfMonth(),
                'end'   => $now->copy()->endOfMonth(),
            ],
            [
                'type'  => 'all_time',
                'key'   => 'all',
                'start' => Carbon::create(2020, 1, 1),
                'end'   => null,
            ],
        ];
    }
}
