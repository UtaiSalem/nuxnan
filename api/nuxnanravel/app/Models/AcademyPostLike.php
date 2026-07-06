<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademyPostLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_post_id',
        'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(AcademyPost::class, 'academy_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
