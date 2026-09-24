<?php

namespace Tests\Feature\Course;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * เฟส 2a: ยืนยัน Course::withCardData() preload user/academy(+card relations)/courseSettings
 * และการเข้าถึงข้อมูลที่ CourseResource ใช้ (user, academy.director/creater/settings) ไม่ query รายแถว
 */
class CourseResourceEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_preloads_card_data(): void
    {
        $owner = User::factory()->create();
        $director = User::factory()->create();
        $academy = Academy::factory()->create(['director' => (string) $director->id]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'academy_id' => $academy->id]);

        $loaded = Course::withCardData()->findOrFail($course->id);

        $this->assertTrue($loaded->relationLoaded('user'), 'course.user ต้อง preload');
        $this->assertTrue($loaded->relationLoaded('academy'), 'course.academy ต้อง preload');
        $this->assertTrue($loaded->relationLoaded('courseSettings'), 'course.courseSettings ต้อง preload');

        // academy ซ้อน ต้องได้ card relations จาก withCardRelations ด้วย
        $this->assertTrue($loaded->academy->relationLoaded('user'), 'academy.user ต้อง preload');
        $this->assertTrue($loaded->academy->relationLoaded('directorUser'), 'academy.directorUser ต้อง preload');
        $this->assertTrue($loaded->academy->relationLoaded('academySetting'), 'academy.academySetting ต้อง preload');
    }

    public function test_accessing_user_and_nested_academy_adds_no_queries(): void
    {
        $owner = User::factory()->create();
        $director = User::factory()->create();
        $academy = Academy::factory()->create(['director' => (string) $director->id]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'academy_id' => $academy->id]);

        $loaded = Course::withCardData()->findOrFail($course->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // ข้อมูลที่ CourseResource ใช้จาก preload — ต้องไม่ยิงคิวรีเพิ่ม
        $u = $loaded->user;
        $ac = $loaded->academy;
        $acUser = $ac->user;
        $acDirector = $ac->directorUser;
        $acSettings = $ac->getSettings();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertNotNull($u);
        $this->assertNotNull($ac);
        $this->assertSame(0, $count, 'user/academy(nested user/director/settings) ต้องมาจาก memory ไม่ query');
    }
}
