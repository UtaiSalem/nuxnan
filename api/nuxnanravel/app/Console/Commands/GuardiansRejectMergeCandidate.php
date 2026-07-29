<?php

namespace App\Console\Commands;

use App\Models\GuardianMergeCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GuardiansRejectMergeCandidate extends Command
{
    protected $signature = 'guardians:reject-merge-candidate {--candidate=} {--note=}';

    public function handle(): int
    {
        DB::transaction(function () {
            $c = GuardianMergeCandidate::lockForUpdate()->findOrFail((int) $this->option('candidate'));
            if ($c->status !== 'pending') {
                throw new \RuntimeException('Candidate is not pending.');
            } $c->update(['status' => 'rejected', 'reviewed_at' => now(), 'note' => $this->option('note')]);
        });

        return self::SUCCESS;
    }
}
