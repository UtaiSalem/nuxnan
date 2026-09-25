<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportDefinition;
use App\Models\Role;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * refreshReport เคยเป็น TODO stub ที่เรียก updateCachedData([]) → ล้างข้อมูลรายงานทิ้ง.
 * ตอนนี้ refresh ใช้ buildReportData() ตัวเดียวกับ generate → ต้องดึงข้อมูลสดทับของเดิม
 * ไม่ใช่ทำให้ว่าง. เทสต์นี้ seed แหล่งข้อมูลจริง (school_attendances) แล้วยืนยันว่า
 * refresh บนรายงานที่ cached_data ว่าง → กลับมามีข้อมูล (ไม่ทำลาย).
 */
class ReportRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_repopulates_data_instead_of_emptying_it(): void
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN'); // bypass academy.permission
        $academy = Academy::factory()->create();

        // seed แหล่งข้อมูลจริงสำหรับ data_source = school_attendances
        $attendanceId = DB::table('school_attendances')->insertGetId([
            'academy_id' => $academy->id,
            'date' => now()->toDateString(),
            'title' => 'คาบเช้า',
            'status' => 'closed',
            'created_by' => $actor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (['present', 'absent', 'present'] as $i => $status) {
            DB::table('school_attendance_records')->insert([
                'attendance_id' => $attendanceId,
                'academy_id' => $academy->id,
                'student_id' => $i + 1,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $definition = ReportDefinition::create([
            'academy_id' => $academy->id,
            'created_by' => $actor->id,
            'code' => 'refresh-'.uniqid(),
            'name' => 'รายงานเข้าเรียน',
            'category' => ReportDefinition::CATEGORY_ATTENDANCE,
            'report_type' => ReportDefinition::TYPE_TABLE,
            'data_source' => 'school_attendances',
            'columns' => ['date', 'title', 'present_count', 'absent_count'],
        ]);

        // รายงานที่ cached_data ว่าง (จำลองสภาพ stale / ถูกล้าง)
        $report = SavedReport::create([
            'academy_id' => $academy->id,
            'definition_id' => $definition->id,
            'user_id' => $actor->id,
            'name' => 'สแนปช็อตเก่า',
            'parameters' => [],
            'cached_data' => [],
            'generated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/saved/{$report->id}/refresh"
        );

        $response->assertStatus(200)->assertJsonPath('success', true);

        $fresh = $report->fresh();
        $this->assertNotEmpty($fresh->cached_data, 'refresh ต้องดึงข้อมูลสดกลับมา ไม่ใช่ล้างเป็น []');

        // ยืนยันว่าเป็นข้อมูลที่ aggregate จริง (1 คาบ: present 2 / absent 1)
        $row = collect($fresh->cached_data)->first();
        $row = is_array($row) ? $row : (array) $row;
        $this->assertEquals(2, (int) $row['present_count']);
        $this->assertEquals(1, (int) $row['absent_count']);
    }
}
