<?php

namespace Tests\Feature\Performance;

use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\AcademyMember;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * เฟส 1b — กัน N+1 ตอน serialize AcademyResource เป็นลิสต์ + **กัน PII รั่ว**
 * ด่านสิทธิ์ (can_view_member_list/can_view_course_list/can_view_content/memberStatus)
 * คุมการเห็นรายชื่อสมาชิกโรงเรียน private → correctness matrix สลับ viewer คือหัวใจ
 */
class AcademyResourceQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function makeAcademy(int $ownerId, string $privacy = 'public', bool $showMembers = true, bool $showCourses = true): Academy
    {
        $academy = Academy::factory()->create(['user_id' => $ownerId]);
        AcademySetting::create([
            'academy_id' => $academy->id,
            'privacy' => $privacy,
            'show_member_list' => $showMembers,
            'show_course_list' => $showCourses,
        ]);

        return $academy;
    }

    public function test_query_count_constant_as_academies_grow(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($viewer, 'api');

        $seed = function (int $n) use ($other) {
            for ($i = 0; $i < $n; $i++) {
                $a = $this->makeAcademy($other->id, 'public');
                // สมาชิกของ "คนอื่น" — ต้องไม่ถูกอ่านมาเป็นสิทธิ์ของ viewer
                AcademyMember::create(['academy_id' => $a->id, 'user_id' => $other->id, 'status' => 2]);
            }
        };

        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $academies = Academy::withViewerCardRelations()->get();
            AcademyResource::collection($academies)->toArray(request());
            $c = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $c;
        };

        $seed(3);
        $small = $measure();

        $seed(9);
        $big = $measure();

        // slack 1 เผื่อ eager-load ซ้อนที่ Laravel ข้ามเมื่อ parent set ว่าง (ไม่ใช่ N+1)
        $this->assertLessThanOrEqual(
            $small + 1,
            $big,
            "query count โตตามจำนวนโรงเรียน (3=$small, 12=$big) — N+1 ยังอยู่"
        );
        $this->assertLessThanOrEqual(15, $big, "จำนวน query คงที่สูงผิดปกติ ($big) — เช็ค preload");
    }

    public function test_visibility_correctness_matrix(): void
    {
        $viewer = User::factory()->create();
        $userB = User::factory()->create();
        $this->actingAs($viewer, 'api');

        // 1) public + แสดงรายชื่อ → คนนอกเห็นได้
        $public = $this->makeAcademy($userB->id, 'public', true, true);

        // 2) public แต่ปิดรายชื่อ/คอร์ส → เห็นเนื้อหาได้ แต่รายชื่อ/คอร์สปิด
        $publicHidden = $this->makeAcademy($userB->id, 'public', false, false);

        // 3) private + viewer ไม่ใช่สมาชิก → ปิดทั้งหมด (PII ไม่รั่ว)
        $privateOutsider = $this->makeAcademy($userB->id, 'private', true, true);

        // 4) private + viewer เป็นสมาชิกอนุมัติแล้ว (status=2) → เห็นได้
        $privateMember = $this->makeAcademy($userB->id, 'private', true, true);
        AcademyMember::create(['academy_id' => $privateMember->id, 'user_id' => $viewer->id, 'status' => 2]);

        // 5) private + viewer รออนุมัติ (status=1) → ยังไม่เห็น
        $privatePending = $this->makeAcademy($userB->id, 'private', true, true);
        AcademyMember::create(['academy_id' => $privatePending->id, 'user_id' => $viewer->id, 'status' => 1]);

        // 6) private + viewer เป็น admin (academy_admins) → เห็นได้แม้ private
        $privateAdmin = $this->makeAcademy($userB->id, 'private', true, true);
        AcademyAdmin::create(['academy_id' => $privateAdmin->id, 'user_id' => $viewer->id]);

        // 7) owner (user_id = viewer) → admin เสมอ
        $owned = $this->makeAcademy($viewer->id, 'private', true, true);

        // 8) 🔴 PII leak guard: userB เป็นสมาชิก private แต่ viewer ไม่ใช่ → viewer ต้องไม่เห็น
        $privateOtherMember = $this->makeAcademy($userB->id, 'private', true, true);
        AcademyMember::create(['academy_id' => $privateOtherMember->id, 'user_id' => $userB->id, 'status' => 2]);

        $ids = [$public->id, $publicHidden->id, $privateOutsider->id, $privateMember->id,
            $privatePending->id, $privateAdmin->id, $owned->id, $privateOtherMember->id];

        $map = [];
        foreach (Academy::withViewerCardRelations()->whereIn('id', $ids)->get() as $a) {
            $map[$a->id] = (new AcademyResource($a))->toArray(request());
        }

        // 1) public
        $this->assertTrue($map[$public->id]['can_view_content']);
        $this->assertTrue($map[$public->id]['can_view_member_list']);
        $this->assertTrue($map[$public->id]['can_view_course_list']);

        // 2) public + ปิดรายชื่อ
        $this->assertTrue($map[$publicHidden->id]['can_view_content']);
        $this->assertFalse($map[$publicHidden->id]['can_view_member_list']);
        $this->assertFalse($map[$publicHidden->id]['can_view_course_list']);

        // 3) private outsider → ปิดหมด
        $this->assertFalse($map[$privateOutsider->id]['can_view_content']);
        $this->assertFalse($map[$privateOutsider->id]['can_view_member_list']);
        $this->assertTrue($map[$privateOutsider->id]['is_restricted']);

        // 4) private member(2) → เห็น
        $this->assertTrue($map[$privateMember->id]['can_view_content']);
        $this->assertTrue($map[$privateMember->id]['can_view_member_list']);
        $this->assertSame(2, (int) $map[$privateMember->id]['memberStatus']);

        // 5) private pending(1) → ยังไม่เห็น แต่ memberStatus = 1
        $this->assertFalse($map[$privatePending->id]['can_view_content']);
        $this->assertFalse($map[$privatePending->id]['can_view_member_list']);
        $this->assertSame(1, (int) $map[$privatePending->id]['memberStatus']);

        // 6) private admin → เห็น
        $this->assertTrue($map[$privateAdmin->id]['can_view_content']);
        $this->assertTrue($map[$privateAdmin->id]['can_view_member_list']);

        // 7) owner → เห็น + authIsAcademyAdmin
        $this->assertTrue($map[$owned->id]['can_view_content']);
        $this->assertTrue($map[$owned->id]['authIsAcademyAdmin']);

        // 8) 🔴 leak guard: viewer ต้องไม่เห็น private ที่มีแต่ userB เป็นสมาชิก
        $this->assertFalse($map[$privateOtherMember->id]['can_view_content'], 'PII รั่ว: viewer เห็น private ของคนอื่น');
        $this->assertFalse($map[$privateOtherMember->id]['can_view_member_list'], 'PII รั่ว: viewer เห็นรายชื่อสมาชิก private ของคนอื่น');
        $this->assertNull($map[$privateOtherMember->id]['memberStatus']);
    }
}
