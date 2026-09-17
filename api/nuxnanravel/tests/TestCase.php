<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * โหมด "สคีมาถูกสร้างไว้ล่วงหน้าแล้ว" — เปิดด้วย `TEST_DB_PREBUILT=1` (ดู `phpunit.mysql.xml`)
     *
     * ใช้ตอนรันเทสต์บน **MySQL จริง**: บอก `RefreshDatabase` ว่า migrate เสร็จไปแล้ว
     * มันจะข้าม `migrate:fresh` เหลือแค่ห่อทุกเทสต์ด้วย transaction แล้ว rollback ตอนจบ
     *
     * ที่ต้องข้ามเพราะ **migrate จากศูนย์บน MySQL ยังพังอยู่** (G25 ใน `.agents/school-admin/11-schedule.md`)
     * สคีมาของฐานข้อมูลเทสต์จึงมาจาก `php artisan test:db:rebuild` (คัดลอกโครงจาก DB dev) แทน
     *
     * โหมดปกติ (sqlite `:memory:`) ไม่ได้รับผลกระทบ — flag ไม่ถูกตั้ง ทุกอย่างเหมือนเดิม
     */
    protected function setUp(): void
    {
        if (static::usesPrebuiltTestDatabase()) {
            RefreshDatabaseState::$migrated = true;
        }

        parent::setUp();

        if (static::usesPrebuiltTestDatabase()) {
            $this->guardAgainstNonTestDatabase();
        }
    }

    protected static function usesPrebuiltTestDatabase(): bool
    {
        $flag = getenv('TEST_DB_PREBUILT');

        if ($flag === false) {
            $flag = $_ENV['TEST_DB_PREBUILT'] ?? $_SERVER['TEST_DB_PREBUILT'] ?? false;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOL);
    }

    /**
     * โหมดนี้เขียนลงฐานข้อมูลจริงแล้วค่อย rollback
     * ⇒ ถ้าเผลอชี้ไปฐานข้อมูลที่ไม่ใช่ของเทสต์ ต้องหยุดก่อนเทสต์แรกจะเขียนอะไร
     */
    private function guardAgainstNonTestDatabase(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === ':memory:' || str_contains($database, 'testing')) {
            return;
        }

        throw new RuntimeException(
            "TEST_DB_PREBUILT เปิดอยู่ แต่ฐานข้อมูลที่ต่อคือ `{$database}` ซึ่งไม่ใช่ฐานข้อมูลสำหรับเทสต์ ".
            '— หยุดก่อนที่เทสต์จะเขียนทับข้อมูลจริง (ชื่อฐานข้อมูลต้องมีคำว่า "testing")'
        );
    }
}
