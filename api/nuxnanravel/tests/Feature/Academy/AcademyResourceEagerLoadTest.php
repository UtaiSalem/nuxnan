<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * เฟส 1a: ยืนยันว่า Academy::withCardRelations() preload user/directorUser/academySetting
 * และ getSettings()/director ไม่ query รายแถวอีก (กัน N+1 ส่วน director/creater/settings ถอย)
 */
class AcademyResourceEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_preloads_card_relations(): void
    {
        $director = User::factory()->create();
        $academy = Academy::factory()->create(['director' => (string) $director->id]);

        $loaded = Academy::withCardRelations()->findOrFail($academy->id);

        $this->assertTrue($loaded->relationLoaded('user'), 'user ต้องถูก eager-load');
        $this->assertTrue($loaded->relationLoaded('directorUser'), 'directorUser ต้องถูก eager-load');
        $this->assertTrue($loaded->relationLoaded('academySetting'), 'academySetting ต้องถูก eager-load');

        // directorUser resolve ถูกคน
        $this->assertNotNull($loaded->directorUser);
        $this->assertSame($director->id, $loaded->directorUser->id);
    }

    public function test_get_settings_uses_loaded_relation_without_query(): void
    {
        $academy = Academy::factory()->create();
        $loaded = Academy::withCardRelations()->findOrFail($academy->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loaded->getSettings();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $count, 'getSettings() ต้องไม่ query เมื่อ eager-load academySetting มาแล้ว');
    }

    public function test_director_and_creater_add_no_queries_when_preloaded(): void
    {
        $director = User::factory()->create();
        $academy = Academy::factory()->create(['director' => (string) $director->id]);

        $loaded = Academy::withCardRelations()->findOrFail($academy->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // เข้าถึงข้อมูลที่ resource ใช้: director + creater(user) — ต้องมาจาก memory
        $d = $loaded->relationLoaded('directorUser') ? $loaded->directorUser : null;
        $u = $loaded->user;
        $this->assertNotNull($d);
        $this->assertNotNull($u);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $count, 'director/creater ต้องไม่ query เมื่อ preload แล้ว');
    }
}
