<?php

namespace Database\Seeders;

use App\Models\ReportDefinition;
use Illuminate\Database\Seeder;

class SchoolReportDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            [
                'code' => 'ATT_DAILY_SUM',
                'name' => 'สรุปจำนวนการเช็คชื่อรายวัน',
                'description' => 'รายงานสรุปจำนวนการเข้าเรียน ขาดเรียน และมาสายของนักเรียนทุกคนในโรงเรียน',
                'category' => 'academic',
                'report_type' => 'tabular',
                'data_source' => ['type' => 'school_attendances'],
                'columns' => ['date', 'title', 'present_count', 'absent_count', 'late_count', 'total_count'],
                'filters' => ['date_range'],
            ],
            [
                'code' => 'FIN_OVERDUE',
                'name' => 'สรุปยอดค้างชำระค่าเทอม',
                'description' => 'รายการนักเรียนที่ยังค้างชำระค่าเล่าเรียนแยกตามระดับชั้น',
                'category' => 'financial',
                'report_type' => 'tabular',
                'data_source' => ['type' => 'tuition_fees'],
                'columns' => ['student_name', 'classroom_name', 'total_amount', 'paid_amount', 'remaining_amount', 'due_date'],
                'filters' => ['academic_year_id', 'semester_id', 'status'],
            ],
            [
                'code' => 'ACAD_AT_RISK',
                'name' => 'วิเคราะห์นักเรียนกลุ่มเสี่ยง',
                'description' => 'รายชื่อนักเรียนที่มีอัตราการเข้าเรียนต่ำกว่าเกณฑ์ หรือมีผลการเรียนวิกฤต',
                'category' => 'academic',
                'report_type' => 'summary',
                'data_source' => ['type' => 'at_risk_students'],
                'columns' => ['student_name', 'attendance_rate', 'gpa', 'risk_factors'],
                'filters' => ['attendance_threshold', 'gpa_threshold'],
            ],
            [
                'code' => 'GAM_POINTS_SUM',
                'name' => 'สรุปการแจกแต้ม Play Learn Earn',
                'description' => 'สถิติการมอบแต้มให้รางวัลนักเรียน แยกตามประเภทกิจกรรม',
                'category' => 'gamification',
                'report_type' => 'chart',
                'data_source' => ['type' => 'points_transactions'],
                'columns' => ['activity_type', 'total_points', 'student_count'],
                'filters' => ['date_range'],
            ],
        ];

        $academy = \App\Models\Academy::first();
        $academyId = $academy ? $academy->id : 1;

        foreach ($definitions as $def) {
            ReportDefinition::updateOrCreate(
                ['code' => $def['code']],
                array_merge($def, [
                    'academy_id' => $academyId,
                    'is_active' => true,
                    'created_by' => 1,
                ])
            );
        }
    }
}
