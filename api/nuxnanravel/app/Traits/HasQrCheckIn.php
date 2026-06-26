<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Shared QR check-in token lifecycle for models with qr_token / qr_token_expires_at columns
 * (e.g. SchoolAttendance, ActivitySession).
 */
trait HasQrCheckIn
{
    // $ttlSeconds = null → valid for entire session (no expiry); pass a value for short-lived display-only QR
    public function generateQrToken(?int $ttlSeconds = null): string
    {
        $token = Str::random(32);
        $this->update([
            'qr_token' => $token,
            'qr_token_expires_at' => $ttlSeconds ? now()->addSeconds($ttlSeconds) : null,
        ]);

        return $token;
    }

    public function isQrTokenValid(string $token): bool
    {
        if ($this->qr_token !== $token) {
            return false;
        }

        // null expiry = valid for entire session (session open/closed is the gate)
        if ($this->qr_token_expires_at === null) {
            return true;
        }

        return now()->lt($this->qr_token_expires_at);
    }
}
