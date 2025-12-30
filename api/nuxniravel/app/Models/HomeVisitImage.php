<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeVisitImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_visit_id',
        'image_path',
        'image_name',
        'image_type',
        'description',
        'uploaded_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the home visit that owns the image
     */
    public function homeVisit()
    {
        return $this->belongsTo(StudentHomeVisit::class, 'home_visit_id');
    }

    /**
     * Get the user who uploaded the image
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
