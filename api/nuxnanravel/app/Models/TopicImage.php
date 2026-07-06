<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'filename',
    ];

    protected $appends = ['image_url'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/images/courses/lessons/topics/'.$this->filename);
    }
}
