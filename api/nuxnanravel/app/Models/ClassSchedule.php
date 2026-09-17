<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassSchedule Model - ตารางเรียน
 */
class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'semester_id',
        'classroom_id',
        'course_id',
        'title',
        'entry_type',
        'period_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'period_number',
        'room',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'period_number' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Day of week constants
    const MONDAY = 1;

    const TUESDAY = 2;

    const WEDNESDAY = 3;

    const THURSDAY = 4;

    const FRIDAY = 5;

    const SATURDAY = 6;

    const SUNDAY = 7;

    const ENTRY_TYPE_COURSE = 'course';

    const ENTRY_TYPE_ACTIVITY = 'activity';

    const ENTRY_TYPE_BREAK = 'break';

    const ENTRY_TYPE_EXAM = 'exam';

    const ENTRY_TYPES = [self::ENTRY_TYPE_COURSE, self::ENTRY_TYPE_ACTIVITY, self::ENTRY_TYPE_BREAK, self::ENTRY_TYPE_EXAM];

    const DAYS = [
        1 => 'วันจันทร์',
        2 => 'วันอังคาร',
        3 => 'วันพุธ',
        4 => 'วันพฤหัสบดี',
        5 => 'วันศุกร์',
        6 => 'วันเสาร์',
        7 => 'วันอาทิตย์',
    ];

    const DAYS_SHORT = [
        1 => 'จ.',
        2 => 'อ.',
        3 => 'พ.',
        4 => 'พฤ.',
        5 => 'ศ.',
        6 => 'ส.',
        7 => 'อา.',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_TEMPORARY = 'temporary';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: ($this->course?->name ?? '');
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }

    public function getDayNameShortAttribute(): string
    {
        return self::DAYS_SHORT[$this->day_of_week] ?? '';
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeByClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeToday($query)
    {
        return $query->where('day_of_week', now()->dayOfWeek ?: 7);
    }

    // Check for conflicts

    /**
     * ปรับค่าขอบเวลาที่รับเข้ามา ('8:00' / 'H:i' / 'H:i:s') ให้เป็น 'H:i:s' เสมอก่อนนำไปเทียบ
     * (ฟังก์ชัน TIME() ของ SQLite ไม่รับชั่วโมงหลักเดียว จึงต้องเติมศูนย์ให้ก่อน)
     */
    protected static function normalizeTime(string $time): string
    {
        $parts = explode(':', trim($time));

        return sprintf(
            '%02d:%02d:%02d',
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0)
        );
    }

    /**
     * ปรับชื่อสถานที่ให้เทียบกันได้: ตัดช่องว่างหัวท้าย + ยุบช่องว่างซ้ำให้เหลือช่องเดียว
     * ค่าว่างถือว่า "ไม่ได้ระบุสถานที่" → คืน null (แปลว่าไม่ต้องตรวจการชน)
     */
    public static function normalizeRoom(?string $room): ?string
    {
        $room = trim(preg_replace('/\s+/u', ' ', (string) $room));

        return $room === '' ? null : $room;
    }

    /**
     * คิวรีฐานของการตรวจชนเวลา — ช่วงเวลาแบบ half-open: ชนเมื่อ start < :end และ end > :start
     * ⇒ คาบที่จบพอดีตอนที่อีกคาบเริ่ม (08:00–09:00 กับ 09:00–10:00) ไม่ถือว่าชนกัน
     *
     * 🔴 หุ้ม TIME() **เฉพาะฝั่งคอลัมน์** — ห้ามหุ้มฝั่ง placeholder:
     * MySQL เก็บคอลัมน์เป็นชนิด TIME (ค่าจริง '09:00:00') แต่ SQLite ที่ใช้ตอนรันเทสต์เก็บเป็นข้อความ
     * ตามรูปแบบของ cast คือ '09:00' แล้วเทียบแบบสตริง ⇒ '09:00' < '09:00:00' เป็นจริง
     * ⇒ ถ้าเทียบตรง ๆ คาบที่ "จบพอดีตอนคาบเดิมเริ่ม" จะถูกนับว่าชนบน SQLite ทั้งที่ MySQL บอกว่าไม่ชน
     * TIME() มีทั้งใน MySQL และ SQLite และคืน 'H:i:s' เหมือนกัน จึงตัดความต่างนี้ทิ้งได้
     *
     * 🔴 แต่ห้ามเขียน `TIME(?)` เด็ดขาด (G26 · เจอตอน SC-S11 ตอนรันเทสต์บน MySQL จริง)
     * บน MySQL 8.4 กับ prepared statement จริง (PDO ไม่ emulate) `TIME(?)` คืน **'00:00:00'**
     * เมื่อค่าที่ผูกมามีนาทีเป็น 00 (เช่น '09:00:00' → 00:00:00 แต่ '09:30:00' → 09:30:00 ถูกต้อง)
     * ⇒ เงื่อนไขแรกเป็นเท็จตลอด ⇒ **คาบที่เริ่ม/จบตรงชั่วโมงจะไม่ถูกนับว่าชนเลย** ซึ่งคือเกือบทุกคาบของโรงเรียนจริง
     * SQLite ไม่มีอาการนี้ — บั๊กจึงซ่อนอยู่ใต้เทสต์เขียวได้นาน
     * ทางที่ถูกคือส่งค่าเป็นสตริง 'H:i:s' ไปตรง ๆ — ทั้ง MySQL และ SQLite เทียบกับ TIME(คอลัมน์) ได้ถูกต้อง
     */
    protected static function overlappingQuery(
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ) {
        $query = static::where('semester_id', $semesterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', self::STATUS_ACTIVE)
            ->whereRaw('TIME(start_time) < ?', [static::normalizeTime($endTime)])
            ->whereRaw('TIME(end_time) > ?', [static::normalizeTime($startTime)]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    public static function hasTeacherConflict(
        int $teacherId,
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        return static::overlappingQuery($semesterId, $dayOfWeek, $startTime, $endTime, $excludeId)
            ->where('teacher_id', $teacherId)
            ->exists();
    }

    public static function hasClassroomConflict(
        int $classroomId,
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        return static::overlappingQuery($semesterId, $dayOfWeek, $startTime, $endTime, $excludeId)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    /**
     * ชนสถานที่: สถานที่ชื่อเดียวกันในโรงเรียนเดียวกันถูกใช้ซ้อนเวลากัน
     * ไม่ระบุสถานที่ (null/ว่าง) = ไม่ตรวจ
     */
    public static function hasRoomConflict(
        int $academyId,
        ?string $room,
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        $room = static::normalizeRoom($room);

        if ($room === null) {
            return false;
        }

        return static::overlappingQuery($semesterId, $dayOfWeek, $startTime, $endTime, $excludeId)
            ->where('academy_id', $academyId)
            ->where('room', $room)
            ->exists();
    }
}
