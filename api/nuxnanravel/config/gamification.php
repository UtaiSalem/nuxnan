<?php

return [
    'level_formula' => [
        'base' => 1000,       // XP for level 1
        'curve' => 'sqrt',     // 'sqrt' | 'linear' | 'exponential'
    ],
    'leaderboard_cycle' => env('LEADERBOARD_CYCLE', 'month'), // week | month
    'cache_ttl_seconds' => 60,
];
