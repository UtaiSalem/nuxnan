<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\RiskEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconcileAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_zero_mismatches_on_clean_data(): void
    {
        User::factory()->create(['wallet' => 0]);
        $course = Course::factory()->create();
        CoursePointAccount::create(['course_id' => $course->id, 'balance' => 0, 'reserved_balance' => 0]);

        $this->assertSame(0, Artisan::call('reconcile:all'));
        $this->assertStringContainsString('0', Artisan::output());
    }

    public function test_command_detects_user_wallet_mismatch(): void
    {
        $user = User::factory()->create(['wallet' => 5.00]);

        $this->assertSame(1, Artisan::call('reconcile:all', ['--emit-risk' => true]));
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'reconcile_user_wallet', 'subject_id' => $user->id]);
    }

    public function test_command_detects_course_balance_mismatch(): void
    {
        $course = Course::factory()->create();
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => 100, 'reserved_balance' => 0]);

        $this->assertSame(1, Artisan::call('reconcile:all', ['--emit-risk' => true]));
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'reconcile_course_balance', 'subject_id' => $account->id]);
    }

    public function test_command_is_idempotent(): void
    {
        User::factory()->create(['wallet' => 5.00]);

        Artisan::call('reconcile:all', ['--emit-risk' => true]);
        Artisan::call('reconcile:all', ['--emit-risk' => true]);

        $this->assertSame(1, RiskEvent::where('rule_name', 'reconcile_user_wallet')->count());
    }
}
