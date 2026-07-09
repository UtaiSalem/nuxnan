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
            'name_slug' => 'original-academy-name',
        ]);

        // Create initial setting
        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
            'auto_accept_members' => true,
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
                'allow_student_registration' => false,
                'allow_parent_registration' => false,
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
        $this->assertEquals('updated-academy-name', $this->academy->name_slug);
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
        $this->assertFalse((bool) $setting->auto_accept_members);
        $this->assertFalse((bool) $setting->allow_student_registration);
        $this->assertFalse((bool) $setting->allow_parent_registration);
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
                'name_slug',
                'privacy',
                'join_mode',
                'allow_student_registration',
                'allow_parent_registration',
                'show_member_list',
                'show_course_list',
            ],
        ]);

        $response->assertJsonPath('academy.privacy', 'private');
        $response->assertJsonPath('academy.join_mode', 'approval');
        $response->assertJsonPath('academy.allow_student_registration', false);
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

    public function test_name_slug_regenerates_and_avoids_collision()
    {
        // Create another academy that has a name resolving to 'new-slug'
        // But names must be unique due to DB unique constraint on name
        Academy::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'New Slug!',
            'name_slug' => 'new-slug',
        ]);

        // Attempt to rename original academy to 'New Slug' (slug would be 'new-slug')
        $response = $this->actingAs($this->owner, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => 'New Slug',
            ]);

        $response->assertStatus(200);
        $this->academy->refresh();
        $this->assertEquals('new-slug-1', $this->academy->name_slug);
    }
}
