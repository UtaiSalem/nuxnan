<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePointTransaction extends Model
{
    protected $guarded = [];

    const TYPE_LESSON_INCOME = 'lesson_income';

    const TYPE_OWNER_WITHDRAW = 'owner_withdraw';

    const TYPE_CAMPAIGN_DEBIT = 'campaign_debit';

    const TYPE_STUDENT_CLAIM = 'student_claim';

    const TYPE_REFUND = 'refund';

    const TYPE_CAMPAIGN_RESERVE = 'campaign_reserve';

    const TYPE_CAMPAIGN_RELEASE = 'campaign_release';

    const TYPE_DONATION_POINT_CREDIT = 'donation_point_credit';

    const TYPE_DONATION_CASH_CREDIT = 'donation_cash_credit';

    const TYPE_ALLOCATION_IN = 'allocation_in';

    const TYPE_AD_REVENUE = 'ad_revenue';

    const TYPE_WITHDRAWAL_RESERVE = 'withdrawal_reserve';

    const TYPE_WITHDRAWAL_RELEASE = 'withdrawal_release';

    const TYPE_WITHDRAWAL_PAID = 'withdrawal_paid';

    protected $fillable = [
        'course_point_account_id', 'course_id', 'lesson_id', 'user_id', 'type', 'amount',
        'balance_before', 'balance_after', 'related_points_transaction_id', 'related_campaign_id',
        'metadata', 'created_by', 'idempotency_key',
    ];

    protected $casts = ['metadata' => 'array'];
}
