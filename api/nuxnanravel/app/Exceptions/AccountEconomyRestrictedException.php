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

    public static function transfersDisabled(): self
    {
        return new self(
            'ระบบปิดการโอนแต้มและเงินระหว่างสมาชิกแล้ว แต้มและเงินใช้ได้เฉพาะสะสม ใช้จ่าย และถอนโดยเจ้าของบัญชีเท่านั้น',
            'transfer',
        );
    }

    public static function memberCouponsDisabled(): self
    {
        return new self(
            'ระบบปิดการสร้างคูปองโดยสมาชิกแล้ว (คูปองเป็นการโอนแต้ม/เงินให้ผู้อื่นทางอ้อม) คูปองที่ยังใช้ได้มีเฉพาะที่ผู้ดูแลระบบออกให้',
            'coupon',
        );
    }
}
