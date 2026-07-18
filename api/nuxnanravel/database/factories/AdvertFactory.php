<?php

namespace Database\Factories;

use App\Enums\CampaignReviewStatus;
use App\Models\Advert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdvertFactory extends Factory
{
    protected $model = Advert::class;

    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'description' => fake()->sentence(), 'user_id' => 1, 'amounts' => 100, 'budget_amount' => 100, 'remaining_views' => 5, 'total_views' => 5, 'duration' => 10, 'review_status' => CampaignReviewStatus::APPROVED, 'campaign_type' => 'advertisement', 'scope_type' => 'public', 'status' => 1, 'impressions_count' => 0, 'slip' => '', 'media_image' => '', 'media_link' => '', 'transfer_date' => today()->toDateString(), 'transfer_time' => now()->format('H:i')];
    }
}
