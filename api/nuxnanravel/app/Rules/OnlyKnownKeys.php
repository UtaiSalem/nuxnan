<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects an array entry that carries keys the endpoint does not read.
 *
 * Laravel's validator ignores unknown keys, so a renamed field keeps being accepted and
 * silently dropped. Migration 2026_06_25_090000 renamed school_attendance_records.remark to
 * remarks and the frontend went on sending `remark` for weeks — every request answered 200
 * and not one teacher's note was saved. The danger in that class of bug is the silence, not
 * the typo, so the unknown key becomes a 422 that names both what was sent and what is read.
 */
class OnlyKnownKeys implements ValidationRule
{
    /**
     * @param  list<string>  $allowed
     */
    public function __construct(private array $allowed) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Not an array — the accompanying `array` rule already reports that.
        if (! is_array($value)) {
            return;
        }

        $unknown = array_diff(array_keys($value), $this->allowed);

        if ($unknown !== []) {
            $fail('ฟิลด์ที่ไม่รู้จัก: '.implode(', ', $unknown).' — รับได้เฉพาะ '.implode(', ', $this->allowed));
        }
    }
}
