<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user whose points/wallet economy has been suspended (for
 * suspected fraud) attempts a balance-changing points or wallet action.
 *
 * Learning access is never affected by this — only the economy is frozen.
 */
class AccountEconomyRestrictedException extends RuntimeException
{
    public function __construct(
        string $message = 'บัญชีนี้ถูกระงับการใช้งานระบบแต้ม/กระเป๋าเงินชั่วคราว',
        public readonly string $system = 'economy',
    ) {
        parent::__construct($message);
    }

    public static function points(): self
    {
        return new self(
            'ระบบสะสมแต้มของบัญชีนี้ถูกระงับชั่วคราวเนื่องจากอยู่ระหว่างการตรวจสอบ',
            'points',
        );
    }

    public static function wallet(): self
    {
        return new self(
            'ระบบกระเป๋าเงิน (Wallet) ของบัญชีนี้ถูกระงับชั่วคราวเนื่องจากอยู่ระหว่างการตรวจสอบ',
            'wallet',
        );
    }
}
