<?php

namespace Tests\Feature\Sports;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S-S6a — the wire contract the score screen depends on.
 *
 * SportsScoringTest already proves the scoring RULES. This file proves the
 * SHAPES: the exact payloads ui/components/academy/sports/* send and the exact
 * JSON keys they read back. Those are what break silently when a controller is
 * refactored, because the page renders an empty card instead of an error.
 */
class SportsScoringWireContractTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $actor;

    private AcademicYear $year;

    /** @var array<int, int> */
    private array $houses = [];

    private SportsEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'sports-admin',
            'display_name_th' => 'Sports',
            'permissions' => ['sports.view', 'sports.manage'],
        ]);
        $this->actor = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->actor->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        // settings คือที่มาของสีคณะบนหน้าจอ — ต้องมากับ relation house_group ด้วย
        $this->houses = collect([['แดง', '#ef4444'], ['น้ำเงิน', '#3b82f6'], ['เขียว', '#22c55e']])
            ->map(fn ($pair) => AcademyGroup::create([
                'academy_id' => $this->academy->id,
                'name' => $pair[0],
                'type' => 'house',
                'settings' => ['color' => $pair[1], 'icon' => 'flag'],
            ])->id)
            ->all();

        $this->edition = SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'กีฬาสี 2569',
            'sequence' => 1,
            'status' => 'active',
            'created_by_user_id' => $this->actor->id,
        ]);

        foreach ($this->houses as $i => $id) {
            SportsEditionHouse::create(['edition_id' => $this->edition->id, 'house_group_id' => $id, 'display_order' => $i]);
        }
    }

    private function base(): string
    {
        return "/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}";
    }

    /** ตรงกับ payload ที่ SportsDisciplineManager.vue ส่งจริงในโหมด team/individual */
    public function test_discipline_store_accepts_the_payload_the_manager_sends(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->postJson($this->base().'/disciplines', [
                'name' => 'ฟุตบอลชาย',
                'type' => 'team',
                'display_order' => 0,
                'max_score' => null,
                'scoring_table' => ['1' => 9, '2' => 8, '3' => 7, '4' => 6, '5' => 5, '6' => 4, '7' => 3, '8' => 2],
            ])
            ->assertCreated();

        // ตัวแก้ตารางคะแนนอ่านกลับด้วยคีย์ string — ถ้ากลายเป็น array 0-based ตารางจะเพี้ยนทั้งใบ
        $response->assertJsonPath('scoring_table.1', 9);
        $response->assertJsonPath('scoring_table.8', 2);
        $this->assertNull($response->json('max_score'));

        // ต้องยืนยันกับ JSON ดิบ ไม่ใช่ผ่าน json() — PHP แปลงคีย์ "1" เป็น int 1 ตอน decode
        // ถ้าวันไหน scoring_table ถูก serialize เป็น array `[9,8,...]` แทน object
        // `table[String(placing)]` ฝั่งหน้าจอจะได้ undefined ทุกอันดับแล้วคะแนนกลายเป็น 0 เงียบ ๆ
        $index = $this->actingAs($this->actor, 'api')->getJson($this->base().'/disciplines')->assertOk();
        $this->assertStringContainsString('"scoring_table":{"1":9,"2":8,', $index->getContent());
    }

    /** โหมด judged ของฟอร์มส่ง scoring_table = null และ max_score เป็นตัวเลข */
    public function test_discipline_update_accepts_the_judged_payload(): void
    {
        $discipline = SportsDiscipline::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'ขบวนพาเหรด',
            'type' => 'team',
            'scoring_table' => ['1' => 9],
            'display_order' => 2,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson($this->base()."/disciplines/{$discipline->id}", [
                'name' => 'ขบวนพาเหรด',
                'type' => 'judged',
                'display_order' => 2,
                'max_score' => 100,
                'scoring_table' => null,
            ])
            ->assertOk()
            ->assertJsonPath('type', 'judged')
            ->assertJsonPath('scoring_table', null);

        // หน้าจอโชว์ "เต็ม N คะแนน" จากค่านี้ — cast decimal:2 ทำให้ออกมาเป็น string
        $this->assertSame('100.00', SportsDiscipline::find($discipline->id)->max_score);
    }

    /** ข้อความยืนยันตอนลบบอกผู้ใช้ว่า "คะแนนไม่ถูกลบ แต่จะไม่ผูกกับรายการอีกต่อไป" — ต้องจริง */
    public function test_deleting_a_discipline_keeps_its_score_entries(): void
    {
        $discipline = SportsDiscipline::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'ชักเย่อ',
            'type' => 'team',
            'scoring_table' => ['1' => 9],
            'display_order' => 0,
        ]);

        $this->actingAs($this->actor, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[0],
            'source' => 'placing',
            'discipline_id' => $discipline->id,
            'placing' => 1,
        ])->assertCreated();

        $this->actingAs($this->actor, 'api')
            ->deleteJson($this->base()."/disciplines/{$discipline->id}")
            ->assertNoContent();

        $entry = SportsScoreEntry::where('edition_id', $this->edition->id)->firstOrFail();
        $this->assertNull($entry->discipline_id);
        $this->assertSame('9.00', $entry->points);
    }

    /** กติกาที่ฟอร์มต้องเคารพ: manual ห้ามมี discipline_id ติดไปด้วย */
    public function test_manual_entry_with_a_discipline_is_rejected(): void
    {
        $discipline = SportsDiscipline::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'วิ่ง 100 เมตร',
            'type' => 'individual',
            'scoring_table' => ['1' => 5],
            'display_order' => 0,
        ]);

        $this->actingAs($this->actor, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[0],
            'source' => 'manual',
            'points' => -5,
            'note' => 'มาสาย',
            'discipline_id' => $discipline->id,
        ])->assertStatus(422);
    }

    /** ประวัติคะแนนอ่านชื่อรายการจาก entry.discipline.name และสถานะยกเลิกจาก voided_at */
    public function test_score_entry_index_carries_the_fields_the_log_reads(): void
    {
        $discipline = SportsDiscipline::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'ขบวนพาเหรด',
            'type' => 'judged',
            'scoring_table' => null,
            'max_score' => 100,
            'display_order' => 0,
        ]);

        $judged = $this->actingAs($this->actor, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[0],
            'source' => 'judged',
            'discipline_id' => $discipline->id,
            'points' => 87.5,
            'note' => 'กรรมการชุดที่ 2',
        ])->assertCreated();

        $this->actingAs($this->actor, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[1],
            'source' => 'manual',
            'points' => -5,
            'note' => 'มาสายพิธีเปิด',
        ])->assertCreated();

        $this->actingAs($this->actor, 'api')
            ->postJson($this->base()."/score-entries/{$judged->json('id')}/void")
            ->assertOk();

        $index = $this->actingAs($this->actor, 'api')->getJson($this->base().'/score-entries')->assertOk();

        $this->assertCount(2, $index->json());
        foreach ($index->json() as $row) {
            foreach (['id', 'house_group_id', 'discipline_id', 'source', 'placing', 'points', 'note', 'voided_at', 'created_at'] as $key) {
                $this->assertArrayHasKey($key, $row, "score entry is missing `$key`, which the log renders");
            }
        }

        $rows = collect($index->json())->keyBy('source');
        // แถวที่ถูกยกเลิกต้องยังอยู่ในผลลัพธ์ ไม่ใช่ถูกกรองทิ้ง — หน้าจอเป็นคนขีดฆ่าเอง
        $this->assertNotNull($rows['judged']['voided_at']);
        $this->assertSame('ขบวนพาเหรด', $rows['judged']['discipline']['name']);
        $this->assertSame('87.50', $rows['judged']['points']);
        // manual ไม่มีรายการแข่ง หน้าจอจึงต้องเขียนว่า "ให้ด้วยมือ" เอง
        $this->assertNull($rows['manual']['discipline']);
        $this->assertSame('-5.00', $rows['manual']['points']);
    }

    /** ตารางคะแนนอ่านชื่อ/สีสำรองจาก standing.house_group */
    public function test_standings_index_carries_the_house_group_relation(): void
    {
        $this->actingAs($this->actor, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[0],
            'source' => 'manual',
            'points' => 10,
            'note' => 'ทดสอบ',
        ])->assertCreated();

        $standings = $this->actingAs($this->actor, 'api')->getJson($this->base().'/standings')->assertOk();

        $row = collect($standings->json())->firstWhere('house_group_id', $this->houses[0]);
        $this->assertSame('แดง', $row['house_group']['name']);
        $this->assertSame('#ef4444', $row['house_group']['settings']['color']);
        $this->assertSame('10.00', $row['total_points']);
        foreach (['rank', 'gold_count', 'silver_count', 'bronze_count', 'computed_at'] as $key) {
            $this->assertArrayHasKey($key, $row);
        }
    }

    /**
     * 🔴 เหตุผลที่หน้าจอต้องวาดแถวคณะสีเองจาก sports_edition_houses
     * ครั้งที่ยังไม่มีใครให้คะแนน endpoint นี้คืน [] ไม่ใช่แถวศูนย์ของทุกคณะ
     */
    public function test_standings_is_empty_until_something_is_awarded_or_rebuilt(): void
    {
        $this->actingAs($this->actor, 'api')
            ->getJson($this->base().'/standings')
            ->assertOk()
            ->assertExactJson([]);

        $rebuilt = $this->actingAs($this->actor, 'api')
            ->postJson($this->base().'/standings/rebuild')
            ->assertOk();

        // rebuild ต้องคืนตารางใหม่กลับมาเลย เพราะหน้าจอเอาผลลัพธ์นี้ไปแทนที่ state ตรง ๆ
        $this->assertCount(count($this->houses), $rebuilt->json());
        $this->assertSame('0.00', $rebuilt->json('0.total_points'));
    }

    /** ผู้ที่มีแค่ sports.view ต้องเปิดหน้าอ่านได้ แต่ให้คะแนนไม่ได้ */
    public function test_a_view_only_member_can_read_but_not_award(): void
    {
        $viewerRole = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'sports-viewer',
            'display_name_th' => 'Sports viewer',
            'permissions' => ['sports.view'],
        ]);
        $viewer = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $viewer->id,
            'academy_role_id' => $viewerRole->id,
            'status' => 2,
        ]);

        $this->actingAs($viewer, 'api')->getJson($this->base().'/standings')->assertOk();
        $this->actingAs($viewer, 'api')->getJson($this->base().'/score-entries')->assertOk();
        $this->actingAs($viewer, 'api')->getJson($this->base().'/disciplines')->assertOk();

        $this->actingAs($viewer, 'api')->postJson($this->base().'/score-entries', [
            'house_group_id' => $this->houses[0],
            'source' => 'manual',
            'points' => 5,
            'note' => 'ไม่ควรผ่าน',
        ])->assertForbidden();

        $this->actingAs($viewer, 'api')->postJson($this->base().'/standings/rebuild')->assertForbidden();
    }
}
