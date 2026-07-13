<?php

namespace App\Enums;

enum StudentCardRequestReason: string
{
    case Lost = 'lost';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case NameChanged = 'name_changed';
    case PhotoOutdated = 'photo_outdated';
    case NewStudent = 'new_student';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lost => 'บัตรหาย',
            self::Damaged => 'บัตรชำรุด',
            self::Expired => 'บัตรหมดอายุ',
            self::NameChanged => 'เปลี่ยนชื่อ–สกุล',
            self::PhotoOutdated => 'รูปถ่ายไม่เป็นปัจจุบัน',
            self::NewStudent => 'นักเรียนใหม่ยังไม่มีบัตร',
            self::Other => 'อื่นๆ',
        };
    }

    /**
     * ประเภทคำร้องที่เหมาะสมตามเหตุผล + สถานะบัตรจริงของนักเรียน
     * ไม่มีบัตร active ในระบบ → ต้องเป็น first_issue เสมอ
     */
    public function deriveRequestType(bool $hasActiveCard): StudentCardRequestType
    {
        if (! $hasActiveCard) {
            return StudentCardRequestType::FirstIssue;
        }

        return match ($this) {
            self::Expired => StudentCardRequestType::Renewal,
            default => StudentCardRequestType::Replacement,
        };
    }
}
