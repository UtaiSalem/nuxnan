<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuardiansVerify extends Command
{
    protected $signature = 'guardians:verify';

    public function handle(): int
    {
        foreach (['guardians', 'student_guardian_links', 'guardian_contacts'] as $t) {
            $this->line($t.'='.DB::table($t)->count());
        }
        $this->line('superseded='.DB::table('guardian_contacts')->whereNotNull('superseded_by_contact_id')->count());
        $this->line('active_contacts='.DB::table('guardian_contacts')->whereNull('superseded_by_contact_id')->count());
        if (Schema::hasTable('student_guardians')) {
            $this->line('student_guardians='.DB::table('student_guardians')->count());
            $this->line('student_guardians_columns='.DB::select("select count(*) c from information_schema.columns where table_schema=database() and table_name='student_guardians'")[0]->c);
        } else {
            $this->line('student_guardians=dropped');
        }
        foreach (DB::table('guardian_merge_candidates')->select('reason', DB::raw('count(*) as group_count'), DB::raw('sum(record_count) as record_count'))->groupBy('reason')->get() as $r) {
            $this->line($r->reason.' groups='.$r->group_count.' records='.$r->record_count);
        }
        $linkIds = DB::table('student_guardian_links')->get()->flatMap(fn ($row) => json_decode($row->legacy_row_ids ?: '[]', true) ?: []);
        $guardianIds = DB::table('guardians')->get()->flatMap(fn ($row) => json_decode($row->legacy_row_ids ?: '[]', true) ?: []);
        $this->line('link_legacy_total='.$linkIds->count().' distinct='.$linkIds->unique()->count());
        $this->line('guardian_legacy_total='.$guardianIds->count());

        return self::SUCCESS;
    }
}
