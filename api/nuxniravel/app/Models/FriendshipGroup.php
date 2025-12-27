<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendshipGroup extends Model
{
    use HasFactory;

    protected $table = 'friendship_groups';

    protected $fillable = [
        'friendship_id',
        'friend_type',
        'friend_id',
        'group_id'
    ];

    protected $casts = [
        'friendship_id' => 'integer',
        'friend_id' => 'integer',
        'group_id' => 'integer'
    ];
}
