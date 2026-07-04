<?php

namespace App\Jobs;

use App\Models\StudentImportBatch;
use App\Services\StudentImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ValidateStudentImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $batchId) {}

    public function handle(StudentImportService $service): void
    {
        $service->validateBatch(StudentImportBatch::findOrFail($this->batchId));
    }

    public function failed(Throwable $exception): void
    {
        StudentImportBatch::whereKey($this->batchId)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'failed']);
    }
}
