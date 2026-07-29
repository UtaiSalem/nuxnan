<?php

namespace App\Console\Commands;

use App\Models\GuardianMergeCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GuardiansScanMergeCandidates extends Command
{
    protected $signature = 'guardians:scan-merge-candidates';

    protected $description = 'Build idempotent guardian merge review candidates';

    public function handle(): int
    {
        $guardians = DB::table('guardians')->orderBy('id')->get();
        $groups = [];
        foreach ($guardians->groupBy('citizen_id') as $key => $rows) {
            if (preg_match('/^\d{13}$/', (string) $key) && $rows->count() > 1 && $rows->pluck('first_name')->map(fn ($v) => $this->normalize($v))->unique()->count() > 1 || preg_match('/^\d{13}$/', (string) $key) && $rows->count() > 1 && $rows->pluck('last_name')->map(fn ($v) => $this->normalize($v))->unique()->count() > 1) {
                $groups[] = ['same_citizen_diff_name', (string) $key, $rows];
            }
        }
        foreach ($guardians->groupBy(fn ($g) => $this->normalize($g->first_name).'|'.$this->normalize($g->last_name)) as $key => $rows) {
            if ($rows->count() > 1 && $rows->pluck('citizen_id')->map(fn ($v) => trim((string) $v))->unique()->count() > 1) {
                $groups[] = ['same_name_diff_citizen', $key, $rows];
            }
        }
        DB::transaction(function () use ($groups) {
            foreach ($groups as [$reason, $key, $rows]) {
                $ids = $rows->pluck('id')->values()->all();
                $candidate = GuardianMergeCandidate::firstOrNew(['reason' => $reason, 'group_key' => $key]);
                if ($candidate->exists && in_array($candidate->status, ['merged', 'rejected'], true)) {
                    continue;
                }
                $candidate->academy_id = $rows->pluck('academy_id')->filter()->first();
                $candidate->guardian_ids = $ids;
                $candidate->record_count = count($ids);
                $candidate->status = $candidate->status ?: 'pending';
                $candidate->save();
            }
        });
        $this->info('pending='.GuardianMergeCandidate::where('status', 'pending')->count());

        return self::SUCCESS;
    }

    private function normalize($value): string
    {
        return preg_replace('/[\x{0E48}\x{0E49}\x{0E4A}\x{0E4B}]/u', '', preg_replace('/\s+/u', ' ', trim((string) $value)));
    }
}
