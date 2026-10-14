<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-deploy orphan-check สำหรับ migration 2026_09_27_000003_repair_academy_donate_claims_foreign_keys.
 *
 * migration นั้นเติม FK 7 คอลัมน์ และจะ **ล้มเหลว (errno 150 / 1452)** ถ้ามี orphan row —
 * ค่าไม่ null แต่ไม่มี parent row ตรง id. รันคำสั่งนี้บน prod ก่อน deploy เพื่อเช็คว่ามี orphan
 * หรือไม่ (read-only — ไม่แก้ข้อมูล). ถ้าเจอ orphan → clear/ซ่อมก่อน ค่อยรัน migration.
 *
 * academy_donate_id ตั้งใจไม่มี FK (legacy academy_donates ไม่ FK-compatible) → ไม่อยู่ในลิสต์นี้.
 *
 * exit code: 0 = ไม่มี orphan (ปลอดภัยที่จะ migrate) · 1 = พบ orphan (ต้องเคลียร์ก่อน) · 2 = ตารางไม่มี.
 */
class AcademyDonateClaimsOrphanCheck extends Command
{
    protected $signature = 'academy:donate-claims-orphan-check
        {--json : Emit machine-readable JSON summary only}
        {--samples=10 : Max sample orphan row ids to list per column}';

    protected $description = 'Read-only pre-deploy check for orphan FK rows in academy_donate_claims (before the 000003 FK-repair migration)';

    /**
     * [column, parent_table, nullable] — ตรงกับ FOREIGN_KEYS ใน migration 000003.
     * nullable=true → ค่า null ถือว่าถูกต้อง (nullOnDelete), เช็คเฉพาะ row ที่ค่าไม่ null.
     */
    private const FOREIGN_KEYS = [
        ['academy_id', 'academies', false],
        ['claimer_id', 'users', false],
        ['suggester_id', 'users', true],
        ['claimer_transaction_id', 'points_transactions', false],
        ['suggester_transaction_id', 'points_transactions', true],
        ['school_transaction_id', 'academy_point_transactions', false],
        ['platform_transaction_id', 'points_transactions', false],
    ];

    public function handle(): int
    {
        $json = (bool) $this->option('json');
        $sampleLimit = max(0, (int) $this->option('samples'));

        if (! Schema::hasTable('academy_donate_claims')) {
            $payload = ['table_exists' => false, 'message' => 'academy_donate_claims table does not exist'];
            $json ? $this->line(json_encode($payload)) : $this->warn('ตาราง academy_donate_claims ไม่มีในฐานข้อมูลนี้ — ข้าม (migration create จะสร้างพร้อม FK ครบ)');

            return 2;
        }

        $totalRows = (int) DB::table('academy_donate_claims')->count();
        $results = [];
        $totalOrphans = 0;

        foreach (self::FOREIGN_KEYS as [$column, $parent, $nullable]) {
            // whereNotExists = เช็คการมีอยู่จริงของ parent row ระดับกายภาพ (ข้าม soft-delete global scope)
            // เพราะ FK constraint สนใจแถวจริงในตาราง ไม่ใช่ scope ของ Eloquent.
            $orphanQuery = DB::table('academy_donate_claims as adc')
                ->whereNotNull("adc.$column")
                ->whereNotExists(function ($q) use ($parent, $column) {
                    $q->select(DB::raw(1))
                        ->from($parent)
                        ->whereColumn("$parent.id", "adc.$column");
                });

            $count = (int) (clone $orphanQuery)->count();
            $samples = $count > 0 && $sampleLimit > 0
                ? (clone $orphanQuery)->orderBy('adc.id')->limit($sampleLimit)->pluck('adc.id')->all()
                : [];

            $totalOrphans += $count;
            $results[] = [
                'column' => $column,
                'parent_table' => $parent,
                'nullable' => $nullable,
                'orphan_count' => $count,
                'sample_ids' => $samples,
            ];
        }

        if ($json) {
            $this->line(json_encode([
                'table_exists' => true,
                'total_rows' => $totalRows,
                'total_orphans' => $totalOrphans,
                'safe_to_migrate' => $totalOrphans === 0,
                'columns' => $results,
            ]));

            return $totalOrphans === 0 ? 0 : 1;
        }

        $this->info('==================================================');
        $this->info('academy_donate_claims — orphan-check (read-only)');
        $this->info('==================================================');
        $this->line("total rows: {$totalRows}");
        $this->newLine();

        foreach ($results as $r) {
            $tag = $r['nullable'] ? 'nullable' : 'NOT NULL';
            if ($r['orphan_count'] === 0) {
                $this->line(sprintf('  ✔ %-26s → %-26s [%s]  0 orphan', $r['column'], $r['parent_table'], $tag));
            } else {
                $this->error(sprintf('  ✗ %-26s → %-26s [%s]  %d ORPHAN', $r['column'], $r['parent_table'], $tag, $r['orphan_count']));
                if (! empty($r['sample_ids'])) {
                    $this->warn('      sample adc.id: '.implode(', ', $r['sample_ids']));
                }
            }
        }

        $this->newLine();
        $this->info('==================================================');
        if ($totalOrphans === 0) {
            $this->info("ผลรวม: 0 orphan → ปลอดภัยที่จะรัน migration 000003 FK-repair");
        } else {
            $this->error("ผลรวม: {$totalOrphans} orphan → ต้องเคลียร์/ซ่อมก่อน ไม่งั้น migration 000003 จะ fail (errno 1452)");
        }
        $this->info('==================================================');

        return $totalOrphans === 0 ? 0 : 1;
    }
}
