<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Students could change their own gender, name, title prefix and date of birth
 * with no approval, because those fields were missing from the per-academy
 * `student_editable_fields` blacklist seeded by
 * 2026_06_18_013944_add_configuration_to_academies_table.
 *
 * This migration extends that blacklist so identity edits become change
 * requests that staff approve. Academies whose setting was customised away
 * from the original default are left untouched in both directions.
 */
return new class extends Migration
{
    /** The blacklist seeded by the 2026_06_18 configuration migration. */
    private const ORIGINAL_FIELDS = [
        'citizen_id',
        'student_id',
        'academic',
        'health',
    ];

    /** Identity fields added on top of the original blacklist. */
    private const IDENTITY_FIELDS = [
        'gender',
        'date_of_birth',
        'title_prefix_th',
        'title_prefix_en',
        'first_name_th',
        'first_name_en',
        'last_name_th',
        'last_name_en',
    ];

    public function up(): void
    {
        $this->rewriteBlacklist(
            self::ORIGINAL_FIELDS,
            array_merge(self::ORIGINAL_FIELDS, self::IDENTITY_FIELDS)
        );
    }

    public function down(): void
    {
        $this->rewriteBlacklist(
            array_merge(self::ORIGINAL_FIELDS, self::IDENTITY_FIELDS),
            self::ORIGINAL_FIELDS
        );
    }

    /**
     * Replace the stored blacklist with $to, but only on rows that currently
     * hold exactly $from. Anything else is a deliberate per-academy override.
     */
    private function rewriteBlacklist(array $from, array $to): void
    {
        $encodedTo = json_encode([
            'mode' => 'blacklist',
            'fields' => array_values($to),
        ]);

        DB::table('academies')
            ->select('id', 'student_editable_fields')
            ->whereNotNull('student_editable_fields')
            ->orderBy('id')
            ->chunkById(200, function ($academies) use ($from, $encodedTo) {
                foreach ($academies as $academy) {
                    if (! $this->matchesBlacklist($academy->student_editable_fields, $from)) {
                        continue;
                    }

                    DB::table('academies')
                        ->where('id', $academy->id)
                        ->update(['student_editable_fields' => $encodedTo]);
                }
            });
    }

    /**
     * True when the stored JSON is a blacklist holding exactly $fields,
     * regardless of order.
     */
    private function matchesBlacklist(?string $stored, array $fields): bool
    {
        $decoded = json_decode((string) $stored, true);

        if (! is_array($decoded) || ($decoded['mode'] ?? null) !== 'blacklist') {
            return false;
        }

        $current = $decoded['fields'] ?? [];

        if (! is_array($current) || count($current) !== count($fields)) {
            return false;
        }

        sort($current);
        sort($fields);

        return $current === $fields;
    }
};
