<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Observers\CourseExternalScoreEntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(CourseExternalScoreEntryObserver::class)]
class CourseExternalScoreEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function externalScore(): BelongsTo
    {
        return $this->belongsTo(CourseExternalScore::class, 'external_score_id');
    }

    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
