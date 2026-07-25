<?php

namespace App\Models;

use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignReviewStatus;
use App\Enums\CampaignScopeType;
use App\Enums\CampaignType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Advert extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'campaign_type' => CampaignType::class,
        'scope_type' => CampaignScopeType::class,
        'payment_status' => CampaignPaymentStatus::class,
        'review_status' => CampaignReviewStatus::class,
        'inherit_to_academy' => 'boolean',
        'impressions_count' => 'integer',
        'budget_amount' => 'decimal:2',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'reviewed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'distributed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    public function getMediaImageAttribute($value)
    {
        // Return full URL to prevent frontend from adding prefix
        return $value ? url('/storage/images/adverts/medias/'.$value) : null;
    }

    public function getSlipAttribute($value)
    {
        return null;
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'advert_viewers', 'advert_id', 'user_id')->withTimestamps();
    }

    public function advertViewers(): HasMany
    {
        return $this->hasMany(AdvertViewer::class, 'advert_id');
    }

    public function deliveryEvents(): HasMany
    {
        return $this->hasMany(CampaignDeliveryEvent::class);
    }
}
