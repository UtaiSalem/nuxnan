<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GradeScale Model - มาตราส่วนเกรด
 */
class GradeScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Default Grade Scale for Thai Schools
    const DEFAULT_GRADES = [
        ['grade' => '4', 'letter_grade' => 'A', 'min_score' => 80, 'max_score' => 100, 'grade_points' => 4.00, 'description' => 'ดีเยี่ยม'],
        ['grade' => '3.5', 'letter_grade' => 'B+', 'min_score' => 75, 'max_score' => 79.99, 'grade_points' => 3.50, 'description' => 'ดีมาก'],
        ['grade' => '3', 'letter_grade' => 'B', 'min_score' => 70, 'max_score' => 74.99, 'grade_points' => 3.00, 'description' => 'ดี'],
        ['grade' => '2.5', 'letter_grade' => 'C+', 'min_score' => 65, 'max_score' => 69.99, 'grade_points' => 2.50, 'description' => 'ค่อนข้างดี'],
        ['grade' => '2', 'letter_grade' => 'C', 'min_score' => 60, 'max_score' => 64.99, 'grade_points' => 2.00, 'description' => 'พอใช้'],
        ['grade' => '1.5', 'letter_grade' => 'D+', 'min_score' => 55, 'max_score' => 59.99, 'grade_points' => 1.50, 'description' => 'อ่อน'],
        ['grade' => '1', 'letter_grade' => 'D', 'min_score' => 50, 'max_score' => 54.99, 'grade_points' => 1.00, 'description' => 'อ่อนมาก'],
        ['grade' => '0', 'letter_grade' => 'F', 'min_score' => 0, 'max_score' => 49.99, 'grade_points' => 0.00, 'description' => 'ไม่ผ่าน'],
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradeScaleItem::class)->orderByDesc('min_score');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // Methods
    public function setAsDefault(): void
    {
        self::where('academy_id', $this->academy_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get grade from score
     */
    public function getGradeFromScore(float $score): ?GradeScaleItem
    {
        return $this->items()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    /**
     * Create default grade scale for academy
     */
    public static function createDefaultForAcademy(int $academyId): self
    {
        $scale = self::create([
            'academy_id' => $academyId,
            'name' => 'มาตรฐาน',
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach (self::DEFAULT_GRADES as $index => $grade) {
            $scale->items()->create([
                'grade' => $grade['grade'],
                'letter_grade' => $grade['letter_grade'],
                'min_score' => $grade['min_score'],
                'max_score' => $grade['max_score'],
                'grade_points' => $grade['grade_points'],
                'description' => $grade['description'],
                'sort_order' => $index,
            ]);
        }

        return $scale;
    }
}
