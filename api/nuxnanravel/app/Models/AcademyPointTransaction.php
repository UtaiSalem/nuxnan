<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyPointTransaction extends Model
{
    protected $guarded = [];

    public const TYPE_DONATION_POINT_CREDIT = 'donation_point_credit';

    public const TYPE_DONATION_CASH_CREDIT = 'donation_cash_credit';

    public const TYPE_ALLOCATION_OUT = 'allocation_out';

    protected $casts = ['metadata' => 'array'];
}
