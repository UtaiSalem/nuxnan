<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SchedulePeriodSet Model — ชุดโครงคาบเรียนของโรงเรียน (SC-S6)
 *
 * โรงเรียนหนึ่งมีได้หลายชุด เช่น "โครงปกติ จ–พฤ" / "วันศุกร์เลิกเร็ว" / "โครง ม.ปลาย"
 * เงื่อนไขว่าชุดไหนใช้เมื่อไร: `days` (1..7) และ `grade_levels` (เช่น ["ม.1"])
 * ค่าว่าง/null = ไม่จำกัด
 */
class SchedulePeriodSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'days',
        'grade_levels',
        'is_default',
        'is_active',
        'display_order',
        'created_by',
    ];

    protected $casts = [
        'days' => 'array',
        'grade_levels' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SchedulePeriod::class, 'set_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * ชุดนี้ใช้กับวันนี้/ระดับชั้นนี้ได้ไหม (ส่ง null = ไม่สนใจเงื่อนไขนั้น)
     */
    public function appliesTo(?int $dayOfWeek = null, ?string $gradeLevel = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $days = is_array($this->days) ? array_map('intval', $this->days) : [];
        if ($dayOfWeek !== null && ! empty($days) && ! in_array((int) $dayOfWeek, $days, true)) {
            return false;
        }

        $levels = is_array($this->grade_levels) ? $this->grade_levels : [];
        if ($gradeLevel !== null && ! empty($levels) && ! in_array($gradeLevel, $levels, true)) {
            return false;
        }

        return true;
    }

    /**
     * เลือกชุดที่ควรใช้จริงกับวัน/ระดับชั้นที่ระบุ
     * เงื่อนไขเจาะจงกว่าชนะ (ระบุระดับชั้น > ระบุวัน > ทั่วไป) ถ้าเสมอกันใช้ `display_order`
     * ถ้าไม่มีชุดไหนเข้าเงื่อนไขเลย ตกไปที่ชุดเริ่มต้นของโรงเรียน
     */
    public static function resolveFor(int $academyId, ?int $dayOfWeek = null, ?string $gradeLevel = null): ?self
    {
        $matched = static::byAcademy($academyId)
            ->active()
            ->ordered()
            ->get()
            ->filter(fn (self $set) => $set->appliesTo($dayOfWeek, $gradeLevel))
            ->sortByDesc(fn (self $set) => (empty($set->grade_levels) ? 0 : 2) + (empty($set->days) ? 0 : 1))
            ->first();

        return $matched ?: static::byAcademy($academyId)->active()->where('is_default', true)->first();
    }
}
