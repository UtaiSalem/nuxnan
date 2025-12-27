<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentHomeVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        // Foreign key to students table
        'student_id',
        
        // Zone Information
        'zone_id',
        
        // Visit Information
        'visit_date',
        'visit_time',
        'visitor_name',
        'visitor_position',
        'visit_status',
        
        // Observations and Notes
        'observations',
        'notes',
        
        // Recommendations
        'recommendations',
        'follow_up',
        'next_visit',
        
        // Audit
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_visit' => 'date',
        'visit_time' => 'datetime:H:i',
    ];

    // Scopes
    public function scopeRecentVisits($query, $days = 30)
    {
        return $query->where('visit_date', '>=', now()->subDays($days));
    }
    
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('visit_status', $status);
    }

    // Accessors
    public function getFormattedVisitDateAttribute()
    {
        return $this->visit_date ? $this->visit_date->format('d/m/Y') : null;
    }
    
    public function getFormattedVisitTimeAttribute()
    {
        return $this->visit_time ? date('H:i', strtotime($this->visit_time)) : null;
    }

    /**
     * Relationship to Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    
    /**
     * Relationship to creator (Teacher/User)
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
    /**
     * Create home visit from student
     */
    public static function createFromStudent(Student $student, array $visitData)
    {
        return self::create(array_merge([
            'student_id' => $student->id,
            'visit_status' => 'completed',
        ], $visitData));
    }

    /**
     * Get the images for this home visit
     */
    public function images()
    {
        return $this->hasMany(HomeVisitImage::class, 'home_visit_id');
    }

    /**
     * Get the evidence images
     */
    public function evidenceImages()
    {
        return $this->images()->where('image_type', 'evidence');
    }

    /**
     * Get the activity images
     */
    public function activityImages()
    {
        return $this->images()->where('image_type', 'activity');
    }

    /**
     * Get all participants for this home visit
     */
    public function participants()
    {
        return $this->hasMany(HomeVisitParticipant::class, 'home_visit_id');
    }

    /**
     * Get all participant names as string
     */
    public function getParticipantNamesAttribute()
    {
        return $this->participants->pluck('participant_name')->implode(', ');
    }

    /**
     * Relationship to zone
     */
    public function zone()
    {
        return $this->belongsTo(HomeVisitZone::class, 'zone_id');
    }
}

