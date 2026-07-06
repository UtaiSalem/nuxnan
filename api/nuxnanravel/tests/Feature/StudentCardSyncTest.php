<?php

namespace Tests\Feature;

use App\Services\StudentCardSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_service_can_be_instantiated()
    {
        $service = new StudentCardSyncService;
        $this->assertInstanceOf(StudentCardSyncService::class, $service);
    }

    public function test_grade_parser_supports_multiple_thai_prefixes(): void
    {
        $service = new StudentCardSyncService;
        $method = new \ReflectionMethod($service, 'numericGradeLevel');

        $this->assertSame(6, $method->invoke($service, 'ม.6'));
        $this->assertSame(3, $method->invoke($service, 'ป.3'));
        $this->assertSame(2, $method->invoke($service, 'อ.2'));
        $this->assertSame(10, $method->invoke($service, '10'));
    }
}
