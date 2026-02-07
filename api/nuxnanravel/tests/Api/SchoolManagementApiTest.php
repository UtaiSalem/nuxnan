<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Academy;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * School Management System API Tests
 * 
 * วิธีรัน:
 * php artisan test tests/Api/SchoolManagementApiTest.php
 * 
 * หรือรันเฉพาะ test:
 * php artisan test --filter=test_can_list_departments
 */
class SchoolManagementApiTest extends TestCase
{
    protected $user;
    protected $academy;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // สร้าง user สำหรับ test
        $this->user = User::first() ?? User::factory()->create();
        $this->academy = Academy::first();
        
        // สร้าง token (ถ้าใช้ Passport/Sanctum)
        // $this->token = $this->user->createToken('test')->accessToken;
    }

    // ==================== PHASE 1: ACADEMIC ====================

    /** @test */
    public function test_can_list_departments()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/departments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function test_can_list_classrooms()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/classrooms");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_curricula()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/curricula");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_class_schedules()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/schedules");

        $response->assertStatus(200);
    }

    // ==================== PHASE 2: FINANCE ====================

    /** @test */
    public function test_can_list_fee_structures()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/fees/structures");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_tuition_fees()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/fees/tuitions");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_payments()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/payments");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_expenses()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/expenses");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_budgets()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/budgets");

        $response->assertStatus(200);
    }

    // ==================== PHASE 3: STAFF ====================

    /** @test */
    public function test_can_list_staff()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/staff");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_staff_attendance()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/staff-attendance");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_leave_requests()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/leave-requests");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_payroll()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/payroll");

        $response->assertStatus(200);
    }

    // ==================== PHASE 4: COMMUNICATION ====================

    /** @test */
    public function test_can_list_announcements()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/announcements");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_events()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/events");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_emergency_alerts()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_message_threads()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/messages/threads");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_meeting_slots()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/meetings/slots");

        $response->assertStatus(200);
    }

    // ==================== PHASE 5: REPORTS & ANALYTICS ====================

    /** @test */
    public function test_can_list_report_definitions()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/reports/definitions");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_dashboard_widgets()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/dashboard/widgets");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_get_analytics_overview()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/analytics/overview");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_kpis()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/analytics/kpis");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_list_alert_rules()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/analytics/alert-rules");

        $response->assertStatus(200);
    }
}
