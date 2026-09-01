<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `avatar` และ `profile_photo_url` เป็น accessor ของ User (อ่านจาก `profile_photo_path`)
 * ไม่ใช่คอลัมน์ในฐานข้อมูล — ถ้าเอาไปใส่ในลิสต์คอลัมน์ของ eager load หรือ get()/select()
 * MySQL จะตอบ `Unknown column` แล้ว endpoint นั้นพัง 500 ทั้งเส้น
 *
 * เทสต์นี้เป็นด่านสแกนซอร์ส เพราะ SQLite ฝั่งเทสต์ **ไม่ฟ้อง** เมื่อ select คอลัมน์ที่ไม่มีอยู่
 * ⇒ เทสต์ที่ assert แค่ status code จับ regression คลาสนี้ไม่ได้เลย
 */
class NoAccessorColumnsInQueriesTest extends TestCase
{
    use RefreshDatabase;

    private const ACCESSOR_ONLY = ['avatar', 'profile_photo_url'];

    public function test_no_table_actually_has_these_columns(): void
    {
        foreach (Schema::getTableListing() as $table) {
            $table = str_contains($table, '.') ? explode('.', $table)[1] : $table;
            foreach (self::ACCESSOR_ONLY as $column) {
                $this->assertFalse(
                    Schema::hasColumn($table, $column),
                    "ตาราง `{$table}` มีคอลัมน์ `{$column}` จริง — สมมติฐานของเทสต์นี้เปลี่ยนแล้ว"
                );
            }
        }
    }

    public function test_no_query_asks_the_database_for_an_accessor_column(): void
    {
        $violations = [];

        foreach ($this->phpFilesUnderApp() as $path) {
            $source = file_get_contents($path);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

            foreach ($this->offendingColumnLists($source) as [$line, $snippet]) {
                $violations[] = "{$relative}:{$line}  {$snippet}";
            }
        }

        $this->assertSame([], $violations, "พบการ select คอลัมน์ที่เป็น accessor (ไม่มีอยู่จริงในฐาน) — MySQL จะตอบ 500:\n".implode("\n", $violations));
    }

    /** @return list<string> */
    private function phpFilesUnderApp(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * หาสองรูปแบบที่พาคอลัมน์ปลอมลงไปถึง SQL ได้จริง:
     *   1) ลิสต์คอลัมน์ของ eager load — 'user:id,name,avatar'
     *   2) array literal ของ get()/select() — ->get(['id', 'name', 'avatar'])
     *
     * @return list<array{0:int,1:string}>
     */
    private function offendingColumnLists(string $source): array
    {
        $found = [];
        $pattern = '/\'[A-Za-z_][A-Za-z0-9_.]*:([A-Za-z0-9_,]+)\'|->(?:get|select)\(\s*\[([^\]]*)\]/';

        if (! preg_match_all($pattern, $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $found;
        }

        foreach ($matches as $match) {
            [$whole, $offset] = $match[0];
            $columns = ($match[1][0] ?? '') !== ''
                ? explode(',', $match[1][0])
                : preg_split('/[^A-Za-z0-9_]+/', $match[2][0] ?? '');

            $hit = array_intersect(
                array_map('trim', $columns ?: []),
                self::ACCESSOR_ONLY
            );

            if ($hit !== []) {
                $found[] = [substr_count(substr($source, 0, $offset), "\n") + 1, trim($whole)];
            }
        }

        return $found;
    }
}
