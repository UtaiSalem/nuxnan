<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyPointTransaction extends Model
{
    protected $guarded = [];

    public const TYPE_DONATION_POINT_CREDIT = 'donation_point_credit';

    public const TYPE_DONATION_CASH_CREDIT = 'donation_cash_credit';

    public const TYPE_ALLOCATION_OUT = 'allocation_out';

    public const TYPE_AD_REVENUE = 'ad_revenue';

    public const TYPE_STUDENT_CLAIM = 'student_claim';

    public const TYPE_DONATION_RESERVE = 'donation_reserve';

    public const TYPE_WITHDRAWAL_RESERVE = 'withdrawal_reserve';

    public const TYPE_WITHDRAWAL_RELEASE = 'withdrawal_release';

    public const TYPE_WITHDRAWAL_PAID = 'withdrawal_paid';

    protected $casts = ['metadata' => 'array'];
}
