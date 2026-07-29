<?php

namespace App\Support;

final class GuardianNameNormalizer
{
    public static function normalize(mixed $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return preg_replace('/[\x{0E48}\x{0E49}\x{0E4A}\x{0E4B}]/u', '', $value);
    }
}
