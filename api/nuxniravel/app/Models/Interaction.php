<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{
    use HasFactory;

    protected $table = 'interactions';

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'relation',
        'relation_value',
        'relation_type'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'relation_value' => 'decimal:2'
    ];
}
