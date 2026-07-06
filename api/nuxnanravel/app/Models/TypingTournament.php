<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingTournament extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'language',
        'difficulty',
        'game_mode',
        'time_limit',
        'starts_at',
        'ends_at',
        'status',
        'prize_1st_xp',
        'prize_1st_pp',
        'prize_2nd_xp',
        'prize_2nd_pp',
        'prize_3rd_xp',
        'prize_3rd_pp',
        'prize_all_xp',
        'total_entries',
        'banner_color',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'time_limit' => 'integer',
        'prize_1st_xp' => 'integer',
        'prize_1st_pp' => 'integer',
        'prize_2nd_xp' => 'integer',
        'prize_2nd_pp' => 'integer',
        'prize_3rd_xp' => 'integer',
        'prize_3rd_pp' => 'integer',
        'prize_all_xp' => 'integer',
        'total_entries' => 'integer',
    ];

    // Relationships
    public function entries(): HasMany
    {
        return $this->hasMany(TypingTournamentEntry::class, 'tournament_id');
    }

    public function topEntries(int $limit = 10): HasMany
    {
        return $this->entries()
            ->with('user:id,name,avatar,profile_photo_url')
            ->orderByDesc('best_score')
            ->limit($limit);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', 'finished');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function getPrizesFor(int $rank): array
    {
        return match ($rank) {
            1 => ['xp' => $this->prize_1st_xp, 'pp' => $this->prize_1st_pp],
            2 => ['xp' => $this->prize_2nd_xp, 'pp' => $this->prize_2nd_pp],
            3 => ['xp' => $this->prize_3rd_xp, 'pp' => $this->prize_3rd_pp],
            default => ['xp' => $this->prize_all_xp, 'pp' => 0],
        };
    }
}
