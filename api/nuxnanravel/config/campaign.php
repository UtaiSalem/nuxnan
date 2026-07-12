<?php

return [
    'ad_price_per_view_second' => (float) env('CAMPAIGN_AD_PRICE_PER_VIEW_SECOND', 0.10),
    'viewer_reward_per_second' => (float) env('CAMPAIGN_VIEWER_REWARD_PER_SECOND', 0.06),
    'referrer_reward_per_second' => (float) env('CAMPAIGN_REFERRER_REWARD_PER_SECOND', 0.02),
    'points_per_reward_baht' => (float) env('CAMPAIGN_POINTS_PER_REWARD_BAHT', 1200),
    'points_rate' => [
        'platform' => (int) env('CAMPAIGN_POINTS_RATE_PLATFORM', 1080),
        'academy' => (int) env('CAMPAIGN_POINTS_RATE_ACADEMY', 1080),
        'course' => (int) env('CAMPAIGN_POINTS_RATE_COURSE', 1080),
    ],
    'revenue_split' => [
        'academy' => (float) env('CAMPAIGN_REVENUE_SPLIT_ACADEMY', 0.70),
        'instructor' => (float) env('CAMPAIGN_REVENUE_SPLIT_INSTRUCTOR', 0.20),
        'platform' => (float) env('CAMPAIGN_REVENUE_SPLIT_PLATFORM', 0.10),
    ],
    'platform_account_code' => (int) env('CAMPAIGN_PLATFORM_ACCOUNT_CODE', 99999999),
    'daily_views_per_user' => 5,
];
