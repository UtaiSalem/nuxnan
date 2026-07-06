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
}
