<?php

namespace App\Support;

class BankAccountNameMatcher
{
    private const PREFIXES = [
        'นาย', 'นาง', 'นางสาว', 'น.ส.', 'น.ส', 'นส.',
        'เด็กชาย', 'เด็กหญิง', 'ด.ช.', 'ด.ญ.', 'ด.ช', 'ด.ญ',
        'mr.', 'mr', 'mrs.', 'mrs', 'ms.', 'ms', 'miss', 'master',
        'ดร.', 'ดร', 'dr.', 'dr',
    ];

    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $s = mb_strtolower(trim($value), 'UTF-8');

        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($s, $prefix.' ') || str_starts_with($s, $prefix)) {
                $s = trim(mb_substr($s, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8'));
            }
        }

        // Collapse whitespace.
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    /**
     * Strict match: both first and last name (normalized, non-empty) must
     * appear inside the normalized account name.
     */
    public static function matches(?string $firstName, ?string $lastName, ?string $accountName): bool
    {
        $first = self::normalize($firstName);
        $last = self::normalize($lastName);
        $account = self::normalize($accountName);

        if ($first === '' || $last === '' || $account === '') {
            return false;
        }

        return str_contains($account, $first) && str_contains($account, $last);
    }
}
