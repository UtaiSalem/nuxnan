<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * สร้างสคีมาของฐานข้อมูลสำหรับเทสต์ โดย **คัดลอกโครงจากฐานข้อมูล dev** (ไม่เอาข้อมูล)
 *
 * ทำไมไม่ใช้ `migrate` ตรง ๆ: migrate จากศูนย์บน MySQL ยังพังอยู่
 * (FK อ้างตารางที่ยังไม่ถูกสร้าง 14 จุด + ตารางจริง 5 ตัวไม่มี migration สร้างเลย — G25)
 * ⇒ ทางที่ได้ MySQL จริงมาทดสอบวันนี้คือคัดลอกโครงของ dev มาใช้
 *
 * ตาราง `migrations` คัดลอกข้อมูลมาด้วย เพื่อให้ยิง `php artisan migrate` ตัวใหม่ ๆ
 * ใส่ฐานข้อมูลนี้เพื่อทดสอบบน MySQL จริงได้ก่อนแตะ dev
 */
class RebuildTestDatabase extends Command
{
    protected $signature = 'test:db:rebuild
        {--target=nuxnan_testing : ชื่อฐานข้อมูลปลายทาง (ต้องมีคำว่า "testing")}
        {--force : ข้ามคำถามยืนยัน}';

    protected $description = 'สร้างฐานข้อมูลสำหรับเทสต์บน MySQL ใหม่ทั้งหมด โดยคัดลอกโครงตาราง (ไม่เอาข้อมูล) จากฐานข้อมูลปัจจุบัน';

    /** ตารางสำรองที่ทำมือไว้ ไม่ใช่สคีมาของแอป */
    private const SKIP_PREFIXES = ['bk_'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('คำสั่งนี้ห้ามรันบน production');

            return self::FAILURE;
        }

        $target = (string) $this->option('target');
        $source = DB::getDatabaseName();

        if (! str_contains($target, 'testing')) {
            $this->error("ชื่อฐานข้อมูลปลายทาง `{$target}` ต้องมีคำว่า \"testing\" — กันพลาดไปทับฐานข้อมูลจริง");

            return self::FAILURE;
        }

        if ($target === $source) {
            $this->error('ฐานข้อมูลปลายทางซ้ำกับต้นทาง');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("ลบและสร้าง `{$target}` ใหม่จากโครงของ `{$source}` ?", true)) {
            return self::SUCCESS;
        }

        [$tables, $views] = $this->inventory($source);

        $this->line("ต้นทาง `{$source}`: ".count($tables).' ตาราง · '.count($views).' วิว');

        DB::statement("DROP DATABASE IF EXISTS `{$target}`");
        DB::statement("CREATE DATABASE `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        Config::set('database.connections.__test_db_rebuild', array_merge(
            config('database.connections.'.config('database.default')),
            ['database' => $target]
        ));

        $to = DB::connection('__test_db_rebuild');
        $to->statement('SET FOREIGN_KEY_CHECKS=0');

        $bar = $this->output->createProgressBar(count($tables) + count($views));

        foreach ($tables as $table) {
            $to->statement($this->createStatement("SHOW CREATE TABLE `{$table}`", 'Create Table'));
            $bar->advance();
        }

        foreach ($views as $view) {
            $to->statement($this->stripDefiner($this->createStatement("SHOW CREATE VIEW `{$view}`", 'Create View')));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $migrations = DB::table('migrations')->get();
        $to->table('migrations')->insert($migrations->map(fn ($row) => (array) $row)->all());

        $to->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("สร้าง `{$target}` เสร็จแล้ว: ".count($tables).' ตาราง · '.count($views).' วิว · '.$migrations->count().' แถวใน migrations');
        $this->line('รันเทสต์บน MySQL จริงด้วย: php artisan test -c phpunit.mysql.xml');

        return self::SUCCESS;
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function inventory(string $schema): array
    {
        $rows = DB::table('information_schema.tables')
            ->select('TABLE_NAME as name', 'TABLE_TYPE as type')
            ->where('TABLE_SCHEMA', $schema)
            ->orderBy('TABLE_NAME')
            ->get()
            ->reject(fn ($row) => $this->isSkipped($row->name));

        return [
            $rows->where('type', 'BASE TABLE')->pluck('name')->values()->all(),
            $rows->where('type', 'VIEW')->pluck('name')->values()->all(),
        ];
    }

    private function isSkipped(string $table): bool
    {
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function createStatement(string $query, string $column): string
    {
        return (string) ((array) DB::selectOne($query))[$column];
    }

    /**
     * `DEFINER=...` ผูกกับผู้ใช้ฐานข้อมูลของเครื่องต้นทาง — เครื่องอื่นสร้างไม่ผ่าน
     */
    private function stripDefiner(string $sql): string
    {
        return (string) preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $sql);
    }
}
