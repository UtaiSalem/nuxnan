<?php

namespace App\Console\Commands;

use App\Models\StudentImportBatch;
use App\Models\User;
use App\Services\Import\StudentRosterCommitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RosterCommitCommand extends Command
{
    protected $signature = 'roster:commit {batch_id} {--dry-run} {--chunk=50}';

    protected $description = 'Commit student roster changes from validated batch to database';

    public function handle(StudentRosterCommitService $commitService): int
    {
        $batchId = $this->argument('batch_id');
        $batch = StudentImportBatch::find($batchId);
        if (! $batch) {
            $this->error("Batch ID {$batchId} not found.");

            return 1;
        }

        if ($batch->status !== 'validated' && $batch->status !== 'processing') {
            $this->error("Batch is in '{$batch->status}' status. Only 'validated' batches can be committed.");

            return 1;
        }

        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $rowsQuery = $batch->rows()
            ->whereNotIn('action', ['conflict'])
            ->where('status', '!=', 'invalid');

        $totalRows = $rowsQuery->count();
        if ($totalRows === 0) {
            $this->warn('No valid rows to commit (excluding conflicts and invalid records).');

            return 0;
        }

        if ($dryRun) {
            $this->info("[Dry Run] Would commit {$totalRows} rows to the database.");

            return 0;
        }

        if (! $this->confirm("Are you sure you want to commit {$totalRows} student roster updates to the database?")) {
            $this->info('Cancelled.');

            return 0;
        }

        $batch->update(['status' => 'processing']);

        // Find a fallback operator user
        $operator = User::where('id', $batch->created_by)->first() ?: User::first();
        if (! $operator) {
            $this->error('No system user found to act as operator.');

            return 1;
        }

        $this->info('Committing updates to database...');
        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        $processedCount = 0;
        $failedCount = 0;

        $rowsQuery->chunk($chunkSize, function ($rows) use ($commitService, $operator, $bar, &$processedCount, &$failedCount) {
            foreach ($rows as $row) {
                try {
                    $commitService->processRow($row, $operator);
                    $processedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to commit roster row ID {$row->id}: ".$e->getMessage());
                    $row->update([
                        'status' => 'failed',
                        'errors' => [['field' => '_system', 'message' => $e->getMessage()]],
                    ]);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        if ($processedCount > 0) {
            $this->info('Synchronizing student cards...');
            $commitService->syncCards($batch, $operator);
        }

        $batch->update([
            'status' => $failedCount > 0 ? 'partial' : 'completed',
            'completed_at' => now(),
            'imported_rows' => $processedCount,
            'skipped_rows' => $batch->rows()->where('action', 'conflict')->count() + $batch->rows()->where('status', 'invalid')->count(),
        ]);

        $this->info("Completed. Committed: {$processedCount}, Failed: {$failedCount}, Skipped: {$batch->skipped_rows}.");

        return 0;
    }
}
