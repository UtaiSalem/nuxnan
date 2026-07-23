<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePointCampaignClaim extends Model
{
    protected $guarded = [];

    protected $casts = ['claimed_at' => 'datetime', 'viewed_at' => 'datetime'];

    public function viewedDonor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewed_donor_id');
    }

    public function viewedDonation(): BelongsTo
    {
        return $this->belongsTo(CourseDonate::class, 'viewed_donation_id');
    }

    public function viewedAd(): BelongsTo
    {
        return $this->belongsTo(Advert::class, 'viewed_ad_id');
    }
}
