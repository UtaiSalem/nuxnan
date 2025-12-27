<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorCounter extends Model
{
    use HasFactory;
    protected $fillable = ['counter'];
    protected $table = 'visitor_counters';
}
