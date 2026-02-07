<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AssessmentCategory Model - หมวดหมู่การประเมิน
 */
class AssessmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'default_weight',
        'icon',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Default Categories
    const DEFAULT_CATEGORIES = [
        ['name' => 'การบ้าน/งานมอบหมาย', 'name_en' => 'Homework/Assignment', 'default_weight' => 20, 'icon' => 'fluent:document-24-filled', 'color' => 'blue'],
        ['name' => 'แบบทดสอบย่อย', 'name_en' => 'Quiz', 'default_weight' => 15, 'icon' => 'fluent:quiz-new-24-filled', 'color' => 'purple'],
        ['name' => 'สอบกลางภาค', 'name_en' => 'Midterm Exam', 'default_weight' => 20, 'icon' => 'fluent:clipboard-text-24-filled', 'color' => 'orange'],
        ['name' => 'สอบปลายภาค', 'name_en' => 'Final Exam', 'default_weight' => 30, 'icon' => 'fluent:certificate-24-filled', 'color' => 'red'],
        ['name' => 'คุณลักษณะ/จิตพิสัย', 'name_en' => 'Affective', 'default_weight' => 10, 'icon' => 'fluent:heart-24-filled', 'color' => 'pink'],
        ['name' => 'โครงงาน/ชิ้นงาน', 'name_en' => 'Project', 'default_weight' => 5, 'icon' => 'fluent:lightbulb-24-filled', 'color' => 'green'],
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function gradebookAssessments(): HasMany
    {
        return $this->hasMany(GradebookAssessment::class, 'category_id');
    }

    // Scopes
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
        return $query->orderBy('sort_order');
    }

    /**
     * Create default categories for academy
     */
    public static function createDefaultsForAcademy(int $academyId): void
    {
        foreach (self::DEFAULT_CATEGORIES as $index => $category) {
            self::create([
                'academy_id' => $academyId,
                'name' => $category['name'],
                'name_en' => $category['name_en'],
                'default_weight' => $category['default_weight'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
