<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    protected $fillable = [
        'poll_id',
        'poll_option_id',
        'user_id',
        'points_earned',
    ];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    public function option()
    {
        // ตัวเลือกของโพลล์เก็บใน question_options (Poll::options() morphMany) — เดิมอ้าง PollOption::class
        // ที่ไม่มีจริง (fatal ถ้าถูกเรียก) · poll_option_id เก็บ question_options.id (ดู PollVoteController)
        return $this->belongsTo(QuestionOption::class, 'poll_option_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
