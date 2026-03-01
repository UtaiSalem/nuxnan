<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

/**
 * SchoolEvent Model - กิจกรรมโรงเรียน
 */
class SchoolEvent extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'academy_id',
        'created_by',
        'title',
        'description',
        'event_type',
        'category',
        'start_datetime',
        'end_datetime',
        'location',
        'location_type',
        'meeting_url',
        'max_participants',
        'requires_registration',
        'registration_deadline',
        'registration_fee',
        'target_audience',
        'cover_image',
        'attachments',
        'is_all_day',
        'is_recurring',
        'recurrence_pattern',
        'status',
        'published_at',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'registration_deadline' => 'datetime',
        'published_at' => 'datetime',
        'registration_fee' => 'decimal:2',
        'target_audience' => 'array',
        'attachments' => 'array',
        'recurrence_pattern' => 'array',
        'requires_registration' => 'boolean',
        'is_all_day' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    // Event type constants
    const TYPE_MEETING = 'meeting';
    const TYPE_HOLIDAY = 'holiday';
    const TYPE_EXAM = 'exam';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_SPORTS = 'sports';
    const TYPE_CEREMONY = 'ceremony';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_MEETING => 'การประชุม',
        self::TYPE_HOLIDAY => 'วันหยุด',
        self::TYPE_EXAM => 'การสอบ',
        self::TYPE_ACTIVITY => 'กิจกรรม',
        self::TYPE_SPORTS => 'กีฬา',
        self::TYPE_CEREMONY => 'พิธีการ',
        self::TYPE_OTHER => 'อื่นๆ',
    ];

    // Location type constants
    const LOCATION_ONSITE = 'onsite';
    const LOCATION_ONLINE = 'online';
    const LOCATION_HYBRID = 'hybrid';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const STATUSES = [
        self::STATUS_DRAFT => 'ร่าง',
        self::STATUS_PUBLISHED => 'เผยแพร่',
        self::STATUS_CANCELLED => 'ยกเลิก',
        self::STATUS_COMPLETED => 'เสร็จสิ้น',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ActivityEnrollment::class, 'event_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'event_id');
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->event_type] ?? '';
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getRegisteredCountAttribute(): int
    {
        return $this->registrations()
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->count();
    }

    public function getAvailableSlotsAttribute(): ?int
    {
        if (!$this->max_participants) return null;
        return max(0, $this->max_participants - $this->registered_count);
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        if (!$this->requires_registration) return false;
        if ($this->status !== self::STATUS_PUBLISHED) return false;
        if ($this->registration_deadline && $this->registration_deadline < now()) return false;
        if ($this->max_participants && $this->available_slots <= 0) return false;
        return true;
    }

    // Methods
    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function hasAvailableSlots(): bool
    {
        if (!$this->max_participants) return true;
        return $this->registered_count < $this->max_participants;
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_datetime', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_datetime')
                    ->orWhere('end_datetime', '>=', now());
            });
    }

    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('end_datetime', '<', now())
                ->orWhere(function ($q2) {
                    $q2->whereNull('end_datetime')
                        ->where('start_datetime', '<', now()->subDay());
                });
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_datetime', '>=', $startDate)
            ->where('start_datetime', '<=', $endDate);
    }
}
