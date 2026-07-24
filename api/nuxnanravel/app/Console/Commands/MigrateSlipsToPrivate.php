<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSlipsToPrivate extends Command
{
    protected $signature = 'slips:migrate-to-private';

    protected $description = 'Copy existing public donation and advert slips to the private disk idempotently';

    public function handle(): int
    {
        foreach (['images/donates', 'images/adverts/slips'] as $directory) {
            foreach (Storage::disk('public')->allFiles($directory) as $file) {
                if (! Storage::disk('local')->exists($file)) {
                    Storage::disk('local')->put($file, Storage::disk('public')->get($file));
                }
            }
        }
        $this->info('Slip migration complete. No public files were moved or deleted.');

        return self::SUCCESS;
    }
}
