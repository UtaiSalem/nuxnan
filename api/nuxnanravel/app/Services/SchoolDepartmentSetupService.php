<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyGroup;
use Illuminate\Support\Facades\DB;

class SchoolDepartmentSetupService
{
    const TEMPLATE = [
        [
            'name' => 'สำนักผู้อำนวยการ',
            'type' => 'office',
            'description' => 'บริหารจัดการภาพรวมของสถานศึกษา',
            'children' => [
                [
                    'name' => 'ฝ่ายบริหารงานวิชาการ',
                    'type' => 'department',
                    'description' => 'บริหารจัดการด้านหลักสูตร การเรียนการสอน การวัดผลประเมินผล',
                    'children' => [
                        ['name' => 'งานทะเบียนและวัดผล', 'type' => 'section', 'description' => 'ทะเบียนนักเรียน การวัดผลประเมินผล การออกเอกสารทางการศึกษา'],
                        ['name' => 'งานพัฒนาหลักสูตร', 'type' => 'section', 'description' => 'พัฒนาหลักสูตรสถานศึกษา แผนการจัดการเรียนรู้'],
                        ['name' => 'งานจัดตารางสอน', 'type' => 'section', 'description' => 'จัดตารางสอน จัดครูเข้าสอน การสอนแทน'],
                        ['name' => 'งานสื่อและเทคโนโลยีทางการศึกษา', 'type' => 'section', 'description' => 'พัฒนาสื่อการเรียนรู้ นวัตกรรมทางการศึกษา'],
                        ['name' => 'กลุ่มสาระฯ ภาษาไทย', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้ภาษาไทย'],
                        ['name' => 'กลุ่มสาระฯ คณิตศาสตร์', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้คณิตศาสตร์'],
                        ['name' => 'กลุ่มสาระฯ วิทยาศาสตร์และเทคโนโลยี', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี'],
                        ['name' => 'กลุ่มสาระฯ สังคมศึกษาฯ', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม'],
                        ['name' => 'กลุ่มสาระฯ สุขศึกษาและพลศึกษา', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา'],
                        ['name' => 'กลุ่มสาระฯ ศิลปะ', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้ศิลปะ'],
                        ['name' => 'กลุ่มสาระฯ การงานอาชีพ', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้การงานอาชีพ'],
                        ['name' => 'กลุ่มสาระฯ ภาษาต่างประเทศ', 'type' => 'academic_group', 'description' => 'กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ'],
                    ],
                ],
                [
                    'name' => 'ฝ่ายบริหารงานกิจการนักเรียน',
                    'type' => 'department',
                    'description' => 'ดูแลระบบช่วยเหลือนักเรียน ระเบียบวินัย กิจกรรมพัฒนาผู้เรียน',
                    'children' => [
                        ['name' => 'งานระบบดูแลช่วยเหลือนักเรียน', 'type' => 'section', 'description' => 'คัดกรองนักเรียน เยี่ยมบ้าน ระบบดูแลช่วยเหลือ'],
                        ['name' => 'งานระเบียบวินัย', 'type' => 'section', 'description' => 'ส่งเสริมระเบียบวินัย การลงโทษ ป้องกันและแก้ไขพฤติกรรม'],
                        ['name' => 'งานกิจกรรมนักเรียน', 'type' => 'section', 'description' => 'กิจกรรมพัฒนาผู้เรียน ลูกเสือ-เนตรนารี สภานักเรียน'],
                        ['name' => 'งานส่งเสริมคุณธรรมจริยธรรม', 'type' => 'section', 'description' => 'ส่งเสริมคุณธรรม จริยธรรม ค่านิยมที่พึงประสงค์'],
                    ],
                ],
                [
                    'name' => 'ฝ่ายบริหารงานบุคคล',
                    'type' => 'department',
                    'description' => 'บริหารงานบุคลากร สรรหา พัฒนา ประเมินผล สวัสดิการ',
                    'children' => [
                        ['name' => 'งานสรรหาและบรรจุแต่งตั้ง', 'type' => 'section', 'description' => 'สรรหาบุคลากร บรรจุแต่งตั้ง โอนย้าย'],
                        ['name' => 'งานพัฒนาบุคลากร', 'type' => 'section', 'description' => 'อบรม สัมมนา พัฒนาวิชาชีพ'],
                        ['name' => 'งานทะเบียนประวัติและสวัสดิการ', 'type' => 'section', 'description' => 'ทะเบียนประวัติ ใบอนุญาตประกอบวิชาชีพ สวัสดิการ การลา'],
                        ['name' => 'งานประเมินผลการปฏิบัติงาน', 'type' => 'section', 'description' => 'ประเมินผลการปฏิบัติงาน เลื่อนเงินเดือน วิทยฐานะ'],
                    ],
                ],
                [
                    'name' => 'ฝ่ายบริหารงานทั่วไป',
                    'type' => 'department',
                    'description' => 'งานธุรการ อาคารสถานที่ เทคโนโลยีสารสนเทศ ประชาสัมพันธ์',
                    'children' => [
                        ['name' => 'งานธุรการและสารบรรณ', 'type' => 'section', 'description' => 'งานสารบรรณ หนังสือราชการ เอกสารทั่วไป'],
                        ['name' => 'งานอาคารสถานที่', 'type' => 'section', 'description' => 'ดูแลอาคารสถานที่ ซ่อมบำรุง สาธารณูปโภค'],
                        ['name' => 'งานเทคโนโลยีสารสนเทศ', 'type' => 'section', 'description' => 'ระบบคอมพิวเตอร์ เครือข่าย ซอฟต์แวร์ ฐานข้อมูล'],
                        ['name' => 'งานประชาสัมพันธ์', 'type' => 'section', 'description' => 'ประชาสัมพันธ์ สื่อสารองค์กร ภาพลักษณ์โรงเรียน'],
                        ['name' => 'งานอนามัยโรงเรียน', 'type' => 'section', 'description' => 'ห้องพยาบาล สุขภาพนักเรียน อาหารกลางวัน'],
                    ],
                ],
                [
                    'name' => 'ฝ่ายบริหารงบประมาณ',
                    'type' => 'department',
                    'description' => 'บริหารงบประมาณ การเงิน บัญชี พัสดุ ทุนการศึกษา',
                    'children' => [
                        ['name' => 'งานการเงิน', 'type' => 'section', 'description' => 'รับ-จ่ายเงิน เก็บรักษาเงิน จัดทำรายงานการเงิน'],
                        ['name' => 'งานบัญชี', 'type' => 'section', 'description' => 'จัดทำบัญชี ตรวจสอบบัญชี งบการเงิน'],
                        ['name' => 'งานพัสดุ', 'type' => 'section', 'description' => 'จัดซื้อจัดจ้าง ทะเบียนพัสดุ ครุภัณฑ์'],
                        ['name' => 'งานทุนการศึกษา', 'type' => 'section', 'description' => 'ทุนการศึกษา เงินอุดหนุน กองทุนกู้ยืม'],
                    ],
                ],
            ],
        ],
    ];

    public function getTemplate(): array
    {
        return self::TEMPLATE;
    }

    public function hasExistingDepartments(Academy $academy): bool
    {
        return $academy->academyGroups()
            ->whereIn('type', ['office', 'department', 'section', 'academic_group'])
            ->exists();
    }

    public function setup(Academy $academy, ?int $createdByUserId = null): array
    {
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($academy, $createdByUserId, &$created, &$skipped) {
            foreach (self::TEMPLATE as $item) {
                $this->createGroupRecursive($academy, $item, null, $createdByUserId, $created, $skipped);
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total' => $created + $skipped,
        ];
    }

    protected function createGroupRecursive(
        Academy $academy,
        array $item,
        ?int $parentId,
        ?int $createdByUserId,
        int &$created,
        int &$skipped,
    ): void {
        $existing = AcademyGroup::where('academy_id', $academy->id)
            ->where('name', $item['name'])
            ->where('type', $item['type'])
            ->first();

        if ($existing) {
            $skipped++;
            $groupId = $existing->id;

            if ($parentId && ! $existing->parent_id) {
                $existing->update(['parent_id' => $parentId]);
            }
        } else {
            $group = AcademyGroup::create([
                'academy_id' => $academy->id,
                'name' => $item['name'],
                'type' => $item['type'],
                'description' => $item['description'] ?? null,
                'parent_id' => $parentId,
                'settings' => $createdByUserId ? ['created_by' => $createdByUserId] : [],
            ]);
            $created++;
            $groupId = $group->id;
        }

        if (! empty($item['children'])) {
            foreach ($item['children'] as $child) {
                $this->createGroupRecursive($academy, $child, $groupId, $createdByUserId, $created, $skipped);
            }
        }
    }
}
