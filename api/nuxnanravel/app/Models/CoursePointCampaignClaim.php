<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePointCampaignClaim extends Model
{
    protected $guarded = [];

    protected $casts = ['claimed_at' => 'datetime'];
}
