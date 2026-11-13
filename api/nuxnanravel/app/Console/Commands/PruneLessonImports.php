<?php

namespace App\Console\Commands;

use App\Services\Import\LessonImportService;
use Illuminate\Console\Command;

class PruneLessonImports extends Command
{
    protected $signature = 'lesson-imports:prune {--hours=24}';

    protected $description = 'Delete abandoned lesson import temp files';

    public function handle(): int
    {
        $n = app(LessonImportService::class)->pruneExpired((int) $this->option('hours'));

        $this->info("Pruned {$n} folder(s).");

        return self::SUCCESS;
    }
}
