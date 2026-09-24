<?php

namespace Tests\Feature\Performance;

use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseResourceQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_courseresource_query_count_constant_as_courses_grow()
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer, 'api');

        // Create 3 courses -> small
        Course::factory()->count(3)->create();

        DB::enableQueryLog();
        $coursesSmall = Course::withViewerCardData()->get();
        CourseResource::collection($coursesSmall)->toArray(request());
        $queriesSmall = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Create 9 more -> big (total 12)
        Course::factory()->count(9)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $coursesBig = Course::withViewerCardData()->get();
        CourseResource::collection($coursesBig)->toArray(request());
        $queriesBig = count(DB::getQueryLog());

        DB::disableQueryLog();

        // หลักการ: query ต้องไม่โตตามจำนวนคอร์ส (คอร์สเพิ่ม 9 → query แทบไม่ขยับ)
        // slack 1 เผื่อ eager-load ซ้อนที่ Laravel ข้ามเมื่อ parent set ว่าง (ไม่ใช่ N+1)
        $this->assertLessThanOrEqual(
            $queriesSmall + 1,
            $queriesBig,
            "query count โตตามจำนวนคอร์ส (3 คอร์ส=$queriesSmall, 12 คอร์ส=$queriesBig) — N+1 ยังอยู่"
        );
        // เพดานกันตัวเลขคงที่เผื่อ preload หลุด (fixed eager-load ~10-15 ตัว)
        $this->assertLessThanOrEqual(18, $queriesBig, "จำนวน query คงที่สูงผิดปกติ ($queriesBig) — เช็ค preload");
    }

    public function test_courseresource_correctness_matrix()
    {
        $viewer = User::factory()->create();
        $userB = User::factory()->create();
        $this->actingAs($viewer, 'api');

        // courseOwned: viewer เป็นเจ้าของ (user_id=viewer) → isCourseAdmin=true, is_owned=false
        $courseOwned = Course::factory()->create(['user_id' => $viewer->id]);

        // courseAdmin: userB เป็นเจ้าของ + viewer เป็น CourseMember role=4 status=1 → isCourseAdmin=true, isMember=true
        $courseAdmin = Course::factory()->create(['user_id' => $userB->id]);
        CourseMember::insert([
            'course_id' => $courseAdmin->id,
            'user_id' => $viewer->id,
            'role' => 4,
            'status' => 1,
            'course_member_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // courseMemberOnly: userB เจ้าของ + viewer เป็น CourseMember role=1 status=1 → isCourseAdmin=false, isMember=true, member_status=1
        $courseMemberOnly = Course::factory()->create(['user_id' => $userB->id]);
        CourseMember::insert([
            'course_id' => $courseMemberOnly->id,
            'user_id' => $viewer->id,
            'role' => 1,
            'status' => 1,
            'course_member_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // courseForeign: userB เจ้าของ + viewer ไม่เกี่ยว + userB เป็น admin ของมัน → isCourseAdmin=false, isMember=false
        $courseForeign = Course::factory()->create(['user_id' => $userB->id]);
        CourseMember::insert([
            'course_id' => $courseForeign->id,
            'user_id' => $userB->id,
            'role' => 4,
            'status' => 1,
            'course_member_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ข้ามเคส isSuperAdmin เนื่องจาก setup ใน test อาจยุ่งยากเกินไป (hasRole)
        // จึงขอข้ามตามที่สเปคอนุญาตให้ทำได้

        // courseOwnedClone: userB เจ้าของคอร์ส X, แล้ว viewer มีคอร์ส clone (source_course_id = X.id) → is_owned=true
        $courseSource = Course::factory()->create(['user_id' => $userB->id]);
        Course::factory()->create([
            'user_id' => $viewer->id,
            'source_course_id' => $courseSource->id,
        ]);

        // courseInvited: userB เจ้าของ + มี CourseInvitation(course_id, invitee_id=viewer, status=pending) → pending_invitation ไม่เป็น null
        $courseInvited = Course::factory()->create(['user_id' => $userB->id]);
        CourseInvitation::insert([
            'course_id' => $courseInvited->id,
            'inviter_id' => $userB->id,
            'invitee_id' => $viewer->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courses = Course::withViewerCardData()
            ->whereIn('id', [
                $courseOwned->id,
                $courseAdmin->id,
                $courseMemberOnly->id,
                $courseForeign->id,
                $courseSource->id,
                $courseInvited->id,
            ])
            ->get();

        $resources = CourseResource::collection($courses)->toArray(request());

        $resourceMap = [];
        foreach ($resources as $res) {
            $resourceMap[$res['id']] = $res;
        }

        // Assert courseOwned
        $resOwned = $resourceMap[$courseOwned->id];
        $this->assertTrue($resOwned['isCourseAdmin']);
        $this->assertFalse($resOwned['is_owned']);

        // Assert courseAdmin
        $resAdmin = $resourceMap[$courseAdmin->id];
        $this->assertTrue($resAdmin['isCourseAdmin']);
        $this->assertTrue($resAdmin['enrollment_status']['is_member']);

        // Assert courseMemberOnly
        $resMember = $resourceMap[$courseMemberOnly->id];
        $this->assertFalse($resMember['isCourseAdmin']);
        $this->assertTrue($resMember['enrollment_status']['is_member']);

        // Assert courseForeign
        $resForeign = $resourceMap[$courseForeign->id];
        $this->assertFalse($resForeign['isCourseAdmin']);
        $this->assertFalse($resForeign['enrollment_status']['is_member']);

        // Assert courseOwnedClone
        $resClone = $resourceMap[$courseSource->id];
        $this->assertTrue($resClone['is_owned']);

        // Assert courseInvited
        $resInvited = $resourceMap[$courseInvited->id];
        $this->assertNotNull($resInvited['pending_invitation']);
    }

    public function test_courseresource_pending_invitation_and_is_owned_scoped_to_viewer()
    {
        $viewer = User::factory()->create();
        $userB = User::factory()->create();
        $this->actingAs($viewer, 'api');

        // ยืนยันว่า invitation ของ userB (invitee=userB) ต้องไม่โผล่เป็น pending_invitation ของ viewer
        $courseInvitedUserB = Course::factory()->create(['user_id' => $userB->id]);
        CourseInvitation::insert([
            'course_id' => $courseInvitedUserB->id,
            'inviter_id' => $viewer->id,
            'invitee_id' => $userB->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ยืนยันว่า clone ของ userB (source_course_id=X, user_id=userB) ต้องไม่ทำให้ viewer ได้ is_owned=true บนคอร์ส X
        $courseSource = Course::factory()->create(['user_id' => $viewer->id]);
        Course::factory()->create([
            'user_id' => $userB->id,
            'source_course_id' => $courseSource->id,
        ]);

        $courses = Course::withViewerCardData()
            ->whereIn('id', [
                $courseInvitedUserB->id,
                $courseSource->id,
            ])
            ->get();

        $resources = CourseResource::collection($courses)->toArray(request());

        $resourceMap = [];
        foreach ($resources as $res) {
            $resourceMap[$res['id']] = $res;
        }

        $resInvited = $resourceMap[$courseInvitedUserB->id];
        $this->assertNull($resInvited['pending_invitation']);

        $resSource = $resourceMap[$courseSource->id];
        $this->assertFalse($resSource['is_owned']);
    }
}
