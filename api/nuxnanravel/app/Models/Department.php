<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Department — ฝ่าย/แผนกของโรงเรียน (ตาราง departments)
 *
 * ตาราง departments ถูกอ้างเป็น FK จาก staff_profiles.department_id และ positions.department_id
 * (migration 2026_02_04_100003_create_staff_system_tables) แต่ไม่เคยมี Eloquent model
 * ทำให้ relation StaffProfile::department()/Position::department() (ที่ belongsTo(Department::class))
 * โยน "Class App\Models\Department not found" เมื่อถูกโหลด — เติม model ที่ migration ตั้งใจให้มี
 */
class Department extends Model
{
    protected $guarded = [];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
