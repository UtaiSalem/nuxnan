<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademySettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $nonOwner;

    protected $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->nonOwner = User::factory()->create();

        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Original Academy Name',
        ]);

        // Create initial setting
        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
        ]);
    }

    public function test_owner_can_update_all_settings_fields()
    {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg');
        $cover = UploadedFile::fake()->image('cover.jpg');

        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'Updated Academy Name',
                'name_en' => 'Updated Academy Name EN',
                'description' => 'This is a description',
                'description_en' => 'This is an EN description',
                'email' => 'updated@academy.com',
                'phone' => '123456789',
                'website' => 'https://updatedacademy.com',
                'address' => '123 St.',
                'province' => 'Bangkok',
                'country' => 'Thailand',
                'privacy' => 'private',
                'join_mode' => 'approval',
                'show_member_list' => false,
                'show_course_list' => false,
                'avatar' => $avatar,
                'cover' => $cover,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Assert DB changes on Academy
        $this->academy->refresh();
        $this->assertEquals('Updated Academy Name', $this->academy->name);
        $this->assertEquals('Updated Academy Name EN', $this->academy->name_en);
        $this->assertEquals('This is a description', $this->academy->description);
        $this->assertEquals('This is an EN description', $this->academy->description_en);
        $this->assertEquals('updated@academy.com', $this->academy->email);
        $this->assertEquals('123456789', $this->academy->phone);
        $this->assertEquals('https://updatedacademy.com', $this->academy->website);
        $this->assertEquals('123 St.', $this->academy->address);
        $this->assertEquals('Bangkok', $this->academy->province);
        $this->assertEquals('Thailand', $this->academy->country);
        $this->assertNotNull($this->academy->logo);
        $this->assertNotNull($this->academy->cover);

        // Assert DB changes on AcademySetting
        $setting = $this->academy->academySetting;
        $this->assertEquals('private', $setting->privacy);
        $this->assertEquals('approval', $setting->join_mode);
        $this->assertFalse((bool) $setting->show_member_list);
        $this->assertFalse((bool) $setting->show_course_list);

        // Verify Resource response contains flattened and correct fields
        $response->assertJsonStructure([
            'success',
            'message',
            'academy' => [
                'id',
                'name',
                'name_en',
                'description',
                'description_en',
                'website',
                'province',
                'country',
                'privacy',
                'join_mode',
                'show_member_list',
                'show_course_list',
            ],
        ]);

        // SET-S8 — คอลัมน์ name_slug ถูกลบทิ้ง payload ต้องไม่มีคีย์นี้อีก
        $response->assertJsonMissingPath('academy.name_slug');

        $response->assertJsonPath('academy.privacy', 'private');
        $response->assertJsonPath('academy.join_mode', 'approval');
        $response->assertJsonMissingPath('academy.allow_student_registration');
        $response->assertJsonMissingPath('academy.allow_parent_registration');
    }

    public function test_non_owner_without_permission_cannot_update_settings()
    {
        $response = $this->actingAs($this->nonOwner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'Hacked Academy',
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_rejects_invalid_values()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => '',
                'website' => 'not-a-url',
                'privacy' => 'invalid-privacy',
                'join_mode' => 'invalid-join',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'website', 'privacy', 'join_mode']);
    }

    public function test_settings_only_update_is_not_served_stale_from_cache()
    {
        // Warm the settings cache with the original values (as a page load would).
        $this->academy->getSettings();

        // Toggle ONLY a setting without changing any academy-table field, so the
        // Academy row stays clean and Academy::updated never fires.
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'Original Academy Name', // unchanged on purpose
                'privacy' => 'private',
                'join_mode' => 'approval',
            ]);

        $response->assertStatus(200);

        // The echoed resource must reflect the new value, not the cached one.
        $response->assertJsonPath('academy.privacy', 'private');
        $response->assertJsonPath('academy.join_mode', 'approval');

        // A fresh read (new request lifecycle) must also be up to date.
        $fresh = Academy::find($this->academy->id);
        $this->assertEquals('private', $fresh->getSettings()->privacy);
        $this->assertEquals('approval', $fresh->getSettings()->join_mode);
    }

    /**
     * SET-S8 — `academies.name` เป็น UNIQUE index ในฐาน
     *
     * ก่อนหน้านี้ด่านกันชนไล่หาชนบน `name_slug` (ผิดคอลัมน์) ⇒ ชื่อซ้ำจริงหลุดไปถึง DB
     * แล้วโยน QueryException ออกมาเป็น 500 พร้อมข้อความ SQL ดิบถึงผู้ใช้
     */
    public function test_renaming_to_a_taken_name_is_rejected_with_422()
    {
        Academy::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Taken Academy Name',
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'Taken Academy Name',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');

        // ชื่อเดิมต้องไม่ถูกแตะ
        $this->assertEquals('Original Academy Name', $this->academy->fresh()->name);
    }

    /**
     * เปลี่ยนเป็นชื่อเดิมของตัวเอง (ไม่ได้เปลี่ยนอะไร) ต้องไม่โดนกฎ unique เล่นงาน
     * — นี่คือเหตุผลที่ต้องใช้ Rule::unique()->ignore($academy->id) ไม่ใช่ 'unique:academies,name' เฉย ๆ
     */
    public function test_keeping_the_same_name_is_still_allowed()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'Original Academy Name',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Original Academy Name', $this->academy->fresh()->name);
    }

    /**
     * ชื่อภาษาไทยต้องบันทึกได้ครบถ้วนโดยไม่มีอะไรมาแปลงหรือกลืนตัวอักษรทิ้ง
     * (เดิม Str::slug จะแปลงชื่อนี้เป็นสตริงว่าง — เป็นที่มาของ SET-S8)
     */
    public function test_thai_name_is_saved_verbatim()
    {
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'โรงเรียนทดสอบภาษาไทย',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('academy.name', 'โรงเรียนทดสอบภาษาไทย');
        $this->assertEquals('โรงเรียนทดสอบภาษาไทย', $this->academy->fresh()->name);
    }
}
