<?php

namespace Tests\Feature\Performance;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * กัน N+1 ถอยในลิสต์ผู้ใช้: หลักคือ "จำนวน query ต้องไม่โตตามจำนวนผู้ใช้"
 * ครอบ 2 ชั้น — (1) scope+UserResource ระดับ unit, (2) endpoint /api/newsfeed ที่ใช้ scope จริง
 */
class UserResourceQueryCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * UserResource บน collection ที่ผ่าน withCardCounts() ต้องใช้ query คงที่
     * ไม่ว่าจะมีผู้ใช้กี่คน (ถ้ามีใครเพิ่ม lazy call ราย user ใน UserResource เคสนี้จะแดง)
     */
    public function test_userresource_collection_over_scope_uses_constant_queries(): void
    {
        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $users = User::withCardCounts()->get();
            UserResource::collection($users)->toArray(request());

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        User::factory()->count(3)->create();
        $small = $measure();

        User::factory()->count(9)->create();
        $big = $measure();

        $this->assertSame(
            $small,
            $big,
            "query count ต้องไม่โตตามจำนวนผู้ใช้ (3 คน=$small, 12 คน=$big) — N+1 อาจกลับมา"
        );
        $this->assertLessThanOrEqual(5, $big, 'preload ควรใช้ query คงที่ไม่กี่ตัว');
    }

    /**
     * endpoint /api/newsfeed (peopleMayKnow) ต้อง preload ผู้ใช้ด้วย scope
     * เพิ่มผู้ใช้ 10 คน จำนวน query ต้องแทบไม่ขยับ (ถ้า controller ถอด withCardCounts ออกจะแดง)
     */
    public function test_newsfeed_people_may_know_does_not_scale_with_user_count(): void
    {
        $viewer = User::factory()->create();

        $measure = function () use ($viewer): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($viewer, 'api')->getJson('/api/newsfeed')->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        User::factory()->count(3)->create();
        $small = $measure();

        User::factory()->count(10)->create();
        $big = $measure();

        // ถ้าไม่ preload: ผู้ใช้เพิ่ม 10 คน จะเพิ่ม query ~50-80 ตัว
        // slack 5 พอกันความแปรผันเล็กน้อย แต่ยังจับ regression ที่เพิ่มเป็นสิบ ๆ ได้
        $this->assertLessThanOrEqual(
            $small + 5,
            $big,
            "newsfeed query โตตามจำนวนผู้ใช้ (ผู้ใช้น้อย=$small, ผู้ใช้มาก=$big) — peopleMayKnow อาจหลุด preload"
        );
    }
}
