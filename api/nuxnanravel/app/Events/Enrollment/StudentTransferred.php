<?php

namespace App\Events\Enrollment;

use App\Models\ClassroomStudent;
use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Student $student,
        public ClassroomStudent $closedFrom,
        public ClassroomStudent $opened,
        public ?string $batchId = null
    ) {}
}
