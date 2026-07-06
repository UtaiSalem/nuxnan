<?php

namespace Tests\Feature;

use App\Services\StudentCardAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_service_can_be_instantiated()
    {
        $service = new StudentCardAuditService;
        $this->assertInstanceOf(StudentCardAuditService::class, $service);
    }
}
