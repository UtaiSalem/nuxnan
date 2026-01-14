<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollComment extends Model
{
    protected $fillable = ['user_id', 'poll_id', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
}
