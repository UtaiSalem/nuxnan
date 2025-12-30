<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Address Model
 */
class StudentAddress extends Model
{
    use HasFactory;

    protected $table = 'student_addresses';

    protected $fillable = [
        'student_id',
        'address_type',
        'house_number',
        'village_number',
        'village_name',
        'alley',
        'road',
        'subdistrict',
        'district',
        'province',
        'postal_code',
        'is_current'
    ];

    protected $casts = [
        'is_current' => 'boolean'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Address type constants
    const TYPE_CURRENT = 'current';
    const TYPE_PERMANENT = 'permanent';
    const TYPE_TEMPORARY = 'temporary';

    public static function getAddressTypes()
    {
        return [
            self::TYPE_CURRENT => 'ปัจจุบัน',
            self::TYPE_PERMANENT => 'ตามทะเบียนบ้าน',
            self::TYPE_TEMPORARY => 'ชั่วคราว'
        ];
    }

    public function getAddressTypeTextAttribute()
    {
        $types = self::getAddressTypes();
        return $types[$this->address_type] ?? $this->address_type;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        
        if ($this->house_number) {
            $parts[] = "เลขที่ {$this->house_number}";
        }
        
        if ($this->village_number) {
            $parts[] = "หมู่ {$this->village_number}";
        }
        
        if ($this->village_name) {
            $parts[] = $this->village_name;
        }
        
        if ($this->alley) {
            $parts[] = "ซอย {$this->alley}";
        }
        
        if ($this->road) {
            $parts[] = "ถ. {$this->road}";
        }
        
        if ($this->subdistrict) {
            $parts[] = "ต. {$this->subdistrict}";
        }
        
        if ($this->district) {
            $parts[] = "อ. {$this->district}";
        }
        
        if ($this->province) {
            $parts[] = "จ. {$this->province}";
        }
        
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        
        return implode(' ', $parts);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('address_type', $type);
    }
}
