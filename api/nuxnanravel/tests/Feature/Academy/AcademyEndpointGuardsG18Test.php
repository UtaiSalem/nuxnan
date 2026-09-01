<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\AcademySetting;
use App\Models\EmergencyAlert;
use App\Models\Learn\Academy\SchoolAttendance;
use App\Models\Learn\Academy\SchoolAttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G18 — endpoint ของโรงเรียนที่เคยเปิดให้ผู้ใช้ที่ล็อกอินคนไหนก็ได้
 *
 * ครอบ 4 กลุ่มที่ค้างไว้: school-attendances · emergency-alerts ·
 * revenue/support-summary · my-role
 *
 * สองเคสที่เป็นหัวใจของรอบนี้:
 *  - `show` ของ school-attendances เคยคืน **รายชื่อ+รูป+สถานะมา/ขาด ของนักเรียนทั้งคาบ**
 *    ให้คนนอก (เอกสาร G18 เดิมตรวจแค่ index ตอนไม่มีคาบเปิด เลยไม่เห็น)
 *  - `check-in` เคยให้คนนอกที่มี qr_token สร้างแถวเช็กชื่อ + รับแต้มได้
 */
class AcademyEndpointGuardsG18Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, [
                'academy_id' => null,
                'name' => $name,
                'is_system' => true,
                'is_active' => true,
            ]));
        }

        $this->owner = User::factory()->create();

        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
        ]);
    }

    private function memberWithSystemRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::whereNull('academy_id')->where('name', $roleName)->firstOrFail();

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        return $user;
    }

    private function openAttendance(): SchoolAttendance
    {
        $attendance = SchoolAttendance::create([
            'academy_id' => $this->academy->id,
            'date' => now()->toDateString(),
            'title' => 'เช็คชื่อหน้าเสาธง',
            'late_minutes' => 15,
            'status' => 'open',
            'created_by' => $this->owner->id,
        ]);

        $attendance->generateQrToken();

        return $attendance->refresh();
    }

    private function activeAlert(): EmergencyAlert
    {
        return EmergencyAlert::create([
            'academy_id' => $this->academy->id,
            'created_by' => $this->owner->id,
            'title' => 'ซ้อมหนีไฟ',
            'message' => 'อพยพออกจากอาคาร 3 ทันที',
            'alert_type' => 'fire',
            'severity' => 'critical',
            'is_active' => true,
        ]);
    }

    // ---------- school-attendances ----------

    public function test_outsider_cannot_list_school_attendances()
    {
        $this->openAttendance();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Not a member of this academy');
    }

    public function test_outsider_cannot_read_the_roster_of_an_attendance_session()
    {
        $attendance = $this->openAttendance();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/{$attendance->id}")
            ->assertStatus(403);
    }

    public function test_outsider_holding_a_valid_qr_token_cannot_check_in()
    {
        $attendance = $this->openAttendance();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->postJson("/api/academies/{$this->academy->id}/school-attendances/{$attendance->id}/check-in", [
                'qr_token' => $attendance->qr_token,
            ])
            ->assertStatus(403);

        // ด่านต้องกันก่อนถึงจุดที่เขียนแถว — ไม่ใช่แค่ตอบ 403 แล้วยังบันทึก
        $this->assertDatabaseMissing('school_attendance_records', [
            'attendance_id' => $attendance->id,
            'student_id' => $outsider->id,
        ]);
    }

    public function test_student_member_can_still_check_in()
    {
        $attendance = $this->openAttendance();
        $student = $this->memberWithSystemRole('student');

        $this->actingAs($student, 'api')
            ->postJson("/api/academies/{$this->academy->id}/school-attendances/{$attendance->id}/check-in", [
                'qr_token' => $attendance->qr_token,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('school_attendance_records', [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
        ]);
    }

    /**
     * นักเรียนต้องยัง list ได้ — วิดเจ็ตเช็กชื่อ (`SchoolAttendanceWidget`) ใช้เส้นนี้
     * หาคาบที่เปิดอยู่วันนี้ก่อนจะโชว์ปุ่มสแกน ถ้าปิดด้วยคีย์ เส้นทางเช็กชื่อของนักเรียนพังทั้งเส้น
     */
    public function test_student_member_can_list_attendance_sessions()
    {
        $this->openAttendance();

        $this->actingAs($this->memberWithSystemRole('student'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances")
            ->assertStatus(200);
    }

    /**
     * หัวใจของ G18 รอบนี้ — คนที่ไม่มีคีย์ `school_attendance.view` เปิดคาบเดียวกันได้
     * แต่ต้องเห็น **เฉพาะแถวของตัวเอง** ไม่ใช่รายชื่อทั้งห้อง
     */
    public function test_student_member_sees_only_their_own_record_not_the_whole_roster()
    {
        $attendance = $this->openAttendance();
        $student = $this->memberWithSystemRole('student');
        $classmate = $this->memberWithSystemRole('student');

        foreach ([$student, $classmate] as $person) {
            SchoolAttendanceRecord::create([
                'attendance_id' => $attendance->id,
                'academy_id' => $this->academy->id,
                'student_id' => $person->id,
                'status' => 'present',
                'check_in_method' => 'manual',
                'recorded_by' => $this->owner->id,
            ]);
        }

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/{$attendance->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.records')
            ->assertJsonPath('summary', null);

        $studentIds = array_column($response->json('data.records'), 'student_id');
        $this->assertSame([$student->id], $studentIds, 'payload ต้องมีแถวของผู้เรียกคนเดียว');
        $this->assertNotContains($classmate->id, $studentIds);
    }

    public function test_teacher_sees_the_whole_roster()
    {
        $attendance = $this->openAttendance();
        $student = $this->memberWithSystemRole('student');
        $classmate = $this->memberWithSystemRole('student');

        foreach ([$student, $classmate] as $person) {
            SchoolAttendanceRecord::create([
                'attendance_id' => $attendance->id,
                'academy_id' => $this->academy->id,
                'student_id' => $person->id,
                'status' => 'present',
                'check_in_method' => 'manual',
                'recorded_by' => $this->owner->id,
            ]);
        }

        $this->actingAs($this->memberWithSystemRole('teacher'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/{$attendance->id}")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.records')
            ->assertJsonPath('summary.total', 2);
    }

    public function test_student_cannot_read_another_students_attendance_history()
    {
        $student = $this->memberWithSystemRole('student');
        $classmate = $this->memberWithSystemRole('student');

        $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/student/{$classmate->id}")
            ->assertStatus(403);

        $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances/student/{$student->id}")
            ->assertStatus(200);
    }

    public function test_teacher_can_list_attendance_sessions()
    {
        $this->openAttendance();

        $this->actingAs($this->memberWithSystemRole('teacher'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances")
            ->assertStatus(200);
    }

    /**
     * หัวใจของการตัดสินใจรอบนี้: คีย์ `school_attendance.manage` เคยถูกแจกให้ role ไว้
     * แต่ไม่มีโค้ดไหนอ่าน (`authorizeManager` ตรวจ `Academy::isAdmin()` อย่างเดียว)
     * ⇒ ครูซึ่งเป็นคนเช็กชื่อจริงโดน 403 มาตลอด
     */
    public function test_teacher_can_open_an_attendance_session()
    {
        $this->actingAs($this->memberWithSystemRole('teacher'), 'api')
            ->postJson("/api/academies/{$this->academy->id}/school-attendances", [
                'date' => now()->toDateString(),
                'title' => 'เช็คชื่อคาบเช้า',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('school_attendances', [
            'academy_id' => $this->academy->id,
            'title' => 'เช็คชื่อคาบเช้า',
        ]);
    }

    public function test_archived_academy_blocks_school_attendances_even_for_a_teacher()
    {
        $this->openAttendance();
        $teacher = $this->memberWithSystemRole('teacher');

        $this->academy->forceFill(['archived_at' => now()])->save();

        $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$this->academy->id}/school-attendances")
            ->assertStatus(403)
            ->assertJsonPath('code', 'academy_archived');
    }

    // ---------- emergency-alerts ----------

    public function test_outsider_cannot_read_active_emergency_alerts()
    {
        $this->activeAlert();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts/active")
            ->assertStatus(403);
    }

    public function test_any_member_can_read_active_emergency_alerts()
    {
        $this->activeAlert();

        $this->actingAs($this->memberWithSystemRole('student'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts/active")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_plain_member_cannot_raise_an_emergency_alert()
    {
        $this->actingAs($this->memberWithSystemRole('student'), 'api')
            ->postJson("/api/academies/{$this->academy->id}/emergency-alerts", [
                'title' => 'ปลอม',
                'message' => 'ประกาศปลอม',
                'alert_type' => 'fire',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('emergency_alerts', ['title' => 'ปลอม']);
    }

    public function test_director_can_raise_an_emergency_alert()
    {
        $this->actingAs($this->memberWithSystemRole('director'), 'api')
            ->postJson("/api/academies/{$this->academy->id}/emergency-alerts", [
                'title' => 'ไฟไหม้อาคาร 3',
                'message' => 'อพยพทันที',
                'alert_type' => 'fire',
                'severity' => 'critical',
            ])
            ->assertStatus(201);
    }

    public function test_acknowledgement_report_needs_the_emergency_view_key()
    {
        $alert = $this->activeAlert();

        $this->actingAs($this->memberWithSystemRole('student'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts/{$alert->id}/acknowledgements")
            ->assertStatus(403);

        $this->actingAs($this->memberWithSystemRole('director'), 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts/{$alert->id}/acknowledgements")
            ->assertStatus(200);
    }

    public function test_archived_academy_blocks_emergency_alerts()
    {
        $this->activeAlert();
        $member = $this->memberWithSystemRole('student');

        $this->academy->forceFill(['archived_at' => now()])->save();

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$this->academy->id}/emergency-alerts/active")
            ->assertStatus(403)
            ->assertJsonPath('code', 'academy_archived');
    }

    // ---------- revenue/support-summary ----------

    public function test_support_summary_stays_open_on_a_public_academy()
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/revenue/support-summary")
            ->assertStatus(200);
    }

    public function test_support_summary_respects_the_private_switch()
    {
        $this->academy->academySetting()->update(['privacy' => 'private']);

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/revenue/support-summary")
            ->assertStatus(403)
            ->assertJsonPath('code', 'academy_private');
    }

    public function test_support_summary_is_blocked_on_an_archived_academy()
    {
        $member = $this->memberWithSystemRole('student');
        $this->academy->forceFill(['archived_at' => now()])->save();

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$this->academy->id}/revenue/support-summary")
            ->assertStatus(403)
            ->assertJsonPath('code', 'academy_archived');
    }

    // ---------- my-role ----------

    /**
     * `my-role` **จงใจ**ไม่มีด่านสมาชิกภาพ — มันรายงานเรื่องของผู้เรียกเองเท่านั้น
     * และ frontend ใช้มันเพื่อรู้ว่า "ฉันยังไม่ได้เป็นสมาชิก" ก่อนจะแสดงปุ่มเข้าร่วม
     * ถ้าใส่ด่านลงไป เส้นทางสมัครเข้าโรงเรียนจะพังทั้งเส้น
     */
    public function test_my_role_stays_open_and_reports_non_membership()
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$this->academy->id}/my-role")
            ->assertStatus(200)
            ->assertJsonPath('is_member', false)
            ->assertJsonPath('is_admin', false)
            ->assertJsonPath('role', null);
    }
}
