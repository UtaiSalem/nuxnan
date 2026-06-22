<?php

namespace App\Events\Enrollment;

use App\Models\RolloverBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RolloverUndone
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RolloverBatch $batch
    ) {}
}
