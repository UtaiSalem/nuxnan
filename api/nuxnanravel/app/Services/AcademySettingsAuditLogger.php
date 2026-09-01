<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademySetting;
use App\Models\MemberActivityLog;

class AcademySettingsAuditLogger
{
    /** คอลัมน์ของ academies ที่ฟอร์มตั้งค่าเขียนได้ */
    private const ACADEMY_FIELDS = [
        'name', 'name_en', 'description', 'description_en',
        'email', 'phone', 'website', 'address', 'province', 'country',
        'slogan', 'type', 'established_year', 'director',
        'donation_enabled', 'student_editable_fields', 'social_media_links',
        'logo', 'cover',
    ];

    /** คอลัมน์ของ academy_settings — คีย์ใน diff ต้องมี prefix 'settings.' */
    private const SETTING_FIELDS = [
        'privacy', 'join_mode', 'show_member_list', 'show_course_list',
        'card_request_flow_enabled',
    ];

    public function snapshot(Academy $academy, ?AcademySetting $setting): array
    {
        $state = [];

        foreach (self::ACADEMY_FIELDS as $field) {
            $state[$field] = $academy->getAttribute($field);
        }

        foreach (self::SETTING_FIELDS as $field) {
            $state['settings.'.$field] = $setting ? $setting->getAttribute($field) : null;
        }

        return $state;
    }

    public function record(Academy $academy, ?AcademySetting $setting, array $before): void
    {
        try {
            // อ่านค่าหลังบันทึกกลับจากฐานเสมอ — ค่าที่ fill() มาจาก multipart เป็นสตริงทั้งหมด
            // ถ้าเอาไปเทียบกับค่าที่มาจากฐาน (established_year เป็น smallint และไม่มี cast)
            // จะได้ diff หลอก int 2510 vs string '2510' ทุกครั้งที่กดบันทึก
            $after = $this->snapshot($academy->fresh() ?? $academy, $setting?->fresh() ?? $setting);
            $oldValues = [];
            $newValues = [];

            foreach ($before as $key => $oldVal) {
                $newVal = $after[$key] ?? null;

                if ($this->normalize($oldVal) !== $this->normalize($newVal)) {
                    $oldValues[$key] = $oldVal;
                    $newValues[$key] = $newVal;
                }
            }

            if (empty($oldValues) && empty($newValues)) {
                return;
            }

            MemberActivityLog::logActivity([
                'academy_id' => $academy->id,
                'action' => MemberActivityLog::ACTION_SETTINGS_UPDATE,
                'action_category' => MemberActivityLog::CATEGORY_SETTINGS,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * ทำให้สองค่าที่เทียบกันอยู่ในรูปเดียวกัน — array เทียบด้วย json ที่เรียงครบทุกชั้น
     * เพื่อไม่ให้การสลับลำดับช่องในฟอร์มกลายเป็น "การเปลี่ยนแปลง"
     */
    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            return json_encode($this->sortRecursive($value));
        }

        return $value;
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        if (array_is_list($value)) {
            sort($value);
        } else {
            ksort($value);
        }

        return $value;
    }
}
