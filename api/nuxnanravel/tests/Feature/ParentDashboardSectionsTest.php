<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\User;
use App\Services\GuardianAccountLinkService;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentDashboardSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '123'.rand(1000000000, 9999999999),
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $link = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $link->guardian];
    }

    private function setupLinkedParent(Academy $academy): array
    {
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();
        $owner = User::factory()->create();

        $req = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $owner, $guardian);
        app(GuardianAccountLinkService::class)->accept($req, $parentUser);

        return [$parentUser, $student];
    }

    public function test_get_announcements_shows_valid_and_authorized_only()
    {
        $academy = Academy::factory()->create();
        [$parentUser] = $this->setupLinkedParent($academy);
        $admin = User::factory()->create();

        // 1. Valid for all
        $validAll = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Valid For All',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'target_audience' => ['all'],
        ]);

        // 2. Not published
        $notPublished = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Not Published',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => false,
            'published_at' => now()->subDay(),
            'target_audience' => ['all'],
        ]);

        // 3. Expired
        $expired = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Expired',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'expires_at' => now()->subHour(),
            'target_audience' => ['all'],
        ]);

        // 4. Target audience teachers
        $teachersOnly = SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Teachers Only',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'target_audience' => ['teachers'],
        ]);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/announcements");

        $response->assertOk()
            ->assertJsonCount(1, 'announcements')
            ->assertJsonPath('announcements.0.id', $validAll->id);
    }

    public function test_get_events_shows_published_and_future_only()
    {
        $academy = Academy::factory()->create();
        [$parentUser] = $this->setupLinkedParent($academy);
        $admin = User::factory()->create();

        // 1. Valid event
        $validEvent = SchoolEvent::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Future Event',
            'status' => 'published',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDays(2),
            'event_type' => 'activity',
        ]);

        // 2. Past event
        SchoolEvent::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Past Event',
            'status' => 'published',
            'start_datetime' => now()->subDays(2),
            'end_datetime' => now()->subDay(),
            'event_type' => 'activity',
        ]);

        // 3. Draft event
        SchoolEvent::create([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Draft Event',
            'status' => 'draft',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDays(2),
            'event_type' => 'activity',
        ]);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/events");

        $response->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.id', $validEvent->id);
    }

    public function test_get_attendance_for_own_child_works()
    {
        $academy = Academy::factory()->create();
        [$parentUser, $student] = $this->setupLinkedParent($academy);
        $admin = User::factory()->create();

        // Create attendance record via DB
        $attendanceId = DB::table('school_attendances')->insertGetId([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'title' => 'Morning Check',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('school_attendance_records')->insert([
            'attendance_id' => $attendanceId,
            'student_id' => $student->id,
            'academy_id' => $academy->id,
            'status' => 'present',
            'checked_in_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/children/{$student->id}/attendance");

        $response->assertOk()
            ->assertJsonCount(1, 'attendance')
            ->assertJsonPath('summary.present', 1)
            ->assertJsonPath('summary.total', 1);
    }

    public function test_get_attendance_for_other_child_fails()
    {
        $academy = Academy::factory()->create();
        [$parentUser] = $this->setupLinkedParent($academy);
        [$otherStudent] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/children/{$otherStudent->id}/attendance");

        $response->assertForbidden();
    }

    public function test_get_fees_empty_when_no_invoices()
    {
        $academy = Academy::factory()->create();
        [$parentUser] = $this->setupLinkedParent($academy);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/fees");

        $response->assertOk()
            ->assertJsonCount(0, 'fees')
            ->assertJsonPath('summary.total_due', 0)
            ->assertJsonPath('summary.total_paid', 0)
            ->assertJsonPath('summary.overdue_count', 0);
    }

    public function test_get_fees_returns_own_student_fees_only()
    {
        $academy = Academy::factory()->create();
        [$parentUser, $student] = $this->setupLinkedParent($academy);
        [$otherStudent] = $this->createStudentWithGuardian($academy);
        $admin = User::factory()->create();

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'active',
        ]);

        $myFee = TuitionFee::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'student_id' => $student->id,
            'invoice_number' => 'INV001',
            'total_amount' => 1000,
            'net_amount' => 1000,
            'balance_amount' => 1000,
            'paid_amount' => 0,
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $otherFee = TuitionFee::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'student_id' => $otherStudent->id,
            'invoice_number' => 'INV002',
            'total_amount' => 2000,
            'net_amount' => 2000,
            'balance_amount' => 2000,
            'paid_amount' => 0,
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/fees");

        $response->assertOk()
            ->assertJsonCount(1, 'fees')
            ->assertJsonPath('fees.0.id', $myFee->id)
            ->assertJsonPath('summary.total_due', 1000)
            ->assertJsonPath('summary.total_paid', 0)
            ->assertJsonPath('summary.overdue_count', 0);
    }
}
