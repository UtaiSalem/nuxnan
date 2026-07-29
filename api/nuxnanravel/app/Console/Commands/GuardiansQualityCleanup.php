<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardiansQualityCleanup extends Command
{
    protected $signature = 'guardians:quality-cleanup';

    protected $description = 'Normalize guardian names and supersede duplicate contacts';

    public function handle(): int
    {
        DB::transaction(function () {
            Storage::makeDirectory('reports');
            $nameFile = fopen(storage_path('app/reports/guardian_name_cleanup.csv'), 'wb');
            fputcsv($nameFile, ['id', 'field', 'before', 'after']);
            DB::table('guardians')->orderBy('id')->get()->each(function ($g) use ($nameFile) {
                foreach (['first_name', 'last_name'] as $field) {
                    $before = (string) $g->{$field};
                    $after = preg_replace('/\s+/u', ' ', trim($before));
                    if ($before !== $after) {
                        fputcsv($nameFile, [$g->id, $field, $before, $after]);
                        DB::table('guardians')->where('id', $g->id)->update([$field => $after, 'updated_at' => now()]);
                    }
                }
            });
            fclose($nameFile);
            $file = fopen(storage_path('app/reports/contact_dedupe.csv'), 'wb');
            fputcsv($file, ['group_key', 'kept_id', 'superseded_id']);
            $groups = DB::table('guardian_contacts')->whereNotNull('guardian_person_id')->get()->groupBy(fn ($c) => $c->guardian_person_id.'|'.$c->contact_type.'|'.trim((string) $c->contact_value));
            foreach ($groups as $key => $rows) {
                if ($rows->count() < 2) {
                    continue;
                } $keep = $rows->sortByDesc('is_primary')->sortByDesc('is_verified')->sortBy('id')->first();
                foreach ($rows->where('id', '!=', $keep->id) as $row) {
                    DB::table('guardian_contacts')->where('id', $row->id)->update(['superseded_by_contact_id' => $keep->id]);
                    fputcsv($file, [$key, $keep->id, $row->id]);
                }
            }
            fclose($file);
        });

        return self::SUCCESS;
    }
}
