<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeVisitParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_visit_id',
        'participant_name',
        'notes',
    ];

    /**
     * Get the home visit that owns the participant
     */
    public function homeVisit()
    {
        return $this->belongsTo(StudentHomeVisit::class, 'home_visit_id');
    }
}
