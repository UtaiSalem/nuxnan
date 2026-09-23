<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * at-risk เคย 500: (1) ไม่ import DB facade (bare DB:: → class not found)
 * (2) tuition_fees.pluck('user_id') — ตารางไม่มีคอลัมน์ user_id (ผูกด้วย student_id → students.id)
 * (3) User::whereIn('id', $studentIds) เอา students.id ไปหาใน users ⇒ คนผิด/ว่าง
 */
class AnalyticsAtRiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_fee_student_is_returned_mapped_to_user()
    {
        $owner = User::factory()->create(['username' => 'owner_u', 'email' => 'o@x.test']);
        $studentUser = User::factory()->create(['username' => 'stu_u', 'email' => 'stu@x.test', 'name' => 'เด็กค้างค่าเทอม']);

        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1', 'display_name' => 'S1', 'description' => 'x',
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id, 'name' => '2569', 'is_current' => true,
            'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        // students.id ≠ users.id — ตั้งใจให้ต่างกันเพื่อจับบั๊กที่เอา student_id ไปหาใน users
        $student = Student::create([
            'user_id' => $studentUser->id, 'academy_id' => $academy->id,
            'student_id' => 'STD-001', 'first_name_th' => 'เด็ก', 'last_name_th' => 'ค้างค่าเทอม',
        ]);

        DB::table('tuition_fees')->insert([
            'academy_id' => $academy->id, 'academic_year_id' => $academicYear->id,
            'student_id' => $student->id, 'invoice_number' => 'INV-001',
            'total_amount' => 5000, 'net_amount' => 5000, 'paid_amount' => 0, 'balance_amount' => 5000,
            'status' => 'overdue', 'issue_date' => '2026-06-01', 'due_date' => '2026-07-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/analytics/at-risk");

        $res->assertStatus(200);
        $res->assertJsonCount(1, 'data');
        // id ต้องเป็น users.id ไม่ใช่ students.id
        $res->assertJsonPath('data.0.id', $studentUser->id);
        $res->assertJsonPath('data.0.name', 'เด็กค้างค่าเทอม');
        $res->assertJsonPath('data.0.risk_factors', ['overdue_fees']);
    }

    public function test_no_at_risk_returns_empty()
    {
        $owner = User::factory()->create(['username' => 'owner_u', 'email' => 'o@x.test']);
        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1', 'display_name' => 'S1', 'description' => 'x',
        ]);

        $res = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/analytics/at-risk");

        $res->assertStatus(200);
        $res->assertJsonCount(0, 'data');
    }
}
