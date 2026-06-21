<?php

namespace App\Events\Enrollment;

use App\Models\RolloverBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RolloverCommitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RolloverBatch $batch
    ) {}
}
