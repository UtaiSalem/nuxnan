<?php

/**
 * Small bcmath helpers for decimal-safe money math. bcmath has no native
 * round() or max(), so we provide them. All inputs are numeric strings.
 */
if (! function_exists('bcround')) {
    /**
     * Round a numeric string to the given scale (half away from zero),
     * matching how THB amounts are normally rounded.
     */
    function bcround(string $number, int $scale = 2): string
    {
        if (! str_contains($number, '.')) {
            return bcadd($number, '0', $scale);
        }

        $delta = '0.'.str_repeat('0', $scale).'5';

        return bccomp($number, '0', $scale + 1) < 0
            ? bcsub($number, $delta, $scale)
            : bcadd($number, $delta, $scale);
    }
}

if (! function_exists('bcmax')) {
    /**
     * Return the larger of two numeric strings at the given scale.
     */
    function bcmax(string $a, string $b, int $scale = 2): string
    {
        return bccomp($a, $b, $scale) >= 0 ? $a : $b;
    }
}
