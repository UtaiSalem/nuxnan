<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeeItem Model - รายการค่าใช้จ่ายในโครงสร้าง
 */
class FeeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'name',
        'code',
        'description',
        'amount',
        'fee_type',
        'is_mandatory',
        'is_refundable',
        'display_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_refundable' => 'boolean',
        'display_order' => 'integer',
    ];

    // Fee types
    const TYPE_TUITION = 'tuition';
    const TYPE_REGISTRATION = 'registration';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_TRANSPORT = 'transport';
    const TYPE_MEAL = 'meal';
    const TYPE_UNIFORM = 'uniform';
    const TYPE_BOOK = 'book';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_TUITION => 'ค่าเล่าเรียน',
        self::TYPE_REGISTRATION => 'ค่าลงทะเบียน',
        self::TYPE_ACTIVITY => 'ค่ากิจกรรม',
        self::TYPE_TRANSPORT => 'ค่ารถรับส่ง',
        self::TYPE_MEAL => 'ค่าอาหาร',
        self::TYPE_UNIFORM => 'ค่าชุดนักเรียน',
        self::TYPE_BOOK => 'ค่าหนังสือ/อุปกรณ์',
        self::TYPE_INSURANCE => 'ค่าประกัน',
        self::TYPE_OTHER => 'อื่นๆ',
    ];

    // Relationships
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->fee_type] ?? 'อื่นๆ';
    }

    // Scopes
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('fee_type', $type);
    }
}
