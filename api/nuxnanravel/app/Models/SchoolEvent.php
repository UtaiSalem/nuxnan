<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SchoolEvent Model - กิจกรรมโรงเรียน
 */
class SchoolEvent extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'group_id',
        'created_by',
        'title',
        'description',
        'event_type',
        'attendance_pattern',
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

    const TYPE_TRAINING = 'training';

    const TYPE_HOLIDAY = 'holiday';

    const TYPE_ORIENTATION = 'orientation';

    const TYPE_EXAM = 'exam';

    const TYPE_ACADEMIC_COMPETITION = 'academic_competition';

    const TYPE_STUDENT_DEVELOPMENT = 'student_development';

    const TYPE_CLUB = 'club';

    const TYPE_ACTIVITY = 'activity';

    const TYPE_SPORTS = 'sports';

    const TYPE_CEREMONY = 'ceremony';

    const TYPE_FIELD_TRIP = 'field_trip';

    const TYPE_VOLUNTEER = 'volunteer';

    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_MEETING => 'การประชุม',
        self::TYPE_TRAINING => 'อบรม/สัมมนา',
        self::TYPE_HOLIDAY => 'วันหยุด',
        self::TYPE_ORIENTATION => 'ปฐมนิเทศ/ปัจฉิมนิเทศ',
        self::TYPE_EXAM => 'การสอบ',
        self::TYPE_ACADEMIC_COMPETITION => 'แข่งขันวิชาการ',
        self::TYPE_STUDENT_DEVELOPMENT => 'กิจกรรมพัฒนาผู้เรียน',
        self::TYPE_CLUB => 'ชมรม/ชุมนุม',
        self::TYPE_ACTIVITY => 'กิจกรรม',
        self::TYPE_SPORTS => 'กีฬา',
        self::TYPE_CEREMONY => 'พิธีการ',
        self::TYPE_FIELD_TRIP => 'ทัศนศึกษา',
        self::TYPE_VOLUNTEER => 'จิตอาสา',
        self::TYPE_OTHER => 'อื่นๆ',
    ];

    // Attendance pattern constants — how enrollment/check-in is handled for this event
    const PATTERN_ONE_TIME = 'one_time';

    const PATTERN_SEMESTER = 'semester';

    const PATTERN_RECURRING = 'recurring';

    const PATTERNS = [
        self::PATTERN_ONE_TIME => 'ครั้งเดียว (ลงทะเบียน+เช็คชื่อวันเดียว)',
        self::PATTERN_SEMESTER => 'ต่อเนื่องรายภาคเรียน (สมัครแล้วเช็คชื่อทุกนัด)',
        self::PATTERN_RECURRING => 'ต่อเนื่องรายวัน/สัปดาห์/เดือน',
    ];

    // Suggested default pattern by type — used as a UI default only, not enforced
    const DEFAULT_PATTERN_BY_TYPE = [
        self::TYPE_STUDENT_DEVELOPMENT => self::PATTERN_SEMESTER,
        self::TYPE_CLUB => self::PATTERN_SEMESTER,
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

    // Owning organizational unit (e.g. a dormitory or club AcademyGroup). Null = school-wide.
    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
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
        if (! $this->max_participants) {
            return null;
        }

        return max(0, $this->max_participants - $this->registered_count);
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        if (! $this->requires_registration) {
            return false;
        }
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }
        if ($this->registration_deadline && $this->registration_deadline < now()) {
            return false;
        }
        if ($this->max_participants && $this->available_slots <= 0) {
            return false;
        }

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
        if (! $this->max_participants) {
            return true;
        }

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
