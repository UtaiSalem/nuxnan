<?php

namespace Tests\Feature\Sports;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SportsAlbum;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\SportsPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SportsAlbumTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $actor;

    private AcademicYear $year;

    /** @var array<int, int> */
    private array $houses = [];

    protected SportsEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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
        $this->year = $this->makeYear(true);
        $this->edition = $this->makeEdition($this->year, 'active');
    }

    private function makeYear(bool $current): AcademicYear
    {
        $year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => $current ? '2569' : '2568',
            'start_date' => $current ? '2026-05-01' : '2025-05-01',
            'end_date' => $current ? '2027-03-31' : '2026-03-31',
            'is_current' => $current,
        ]);

        if ($this->houses === []) {
            $this->houses = collect(['แดง', 'น้ำเงิน', 'เขียว', 'เหลือง'])
                ->map(fn ($name) => AcademyGroup::create([
                    'academy_id' => $this->academy->id,
                    'name' => $name,
                    'type' => 'house',
                ])->id)
                ->all();
        }

        return $year;
    }

    private function makeEdition(AcademicYear $year, string $status = 'draft'): SportsEdition
    {
        $sequence = SportsEdition::where('academy_id', $year->academy_id)
            ->where('academic_year_id', $year->id)
            ->max('sequence') ?? 0;

        $edition = SportsEdition::create([
            'academy_id' => $year->academy_id,
            'academic_year_id' => $year->id,
            'name' => 'Test',
            'sequence' => $sequence + 1,
            'status' => $status,
            'created_by_user_id' => $this->actor->id,
        ]);

        foreach ($this->houses as $i => $id) {
            SportsEditionHouse::create(['edition_id' => $edition->id, 'house_group_id' => $id, 'display_order' => $i]);
        }

        return $edition;
    }

    private function makeDiscipline(array $attributes = []): SportsDiscipline
    {
        return SportsDiscipline::create(array_merge([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'วิ่ง 100 เมตร',
            'type' => 'team',
            'format' => 'none',
            'scoring_table' => ['1' => 9, '2' => 8, '3' => 7],
            'max_score' => null,
            'display_order' => 1,
        ], $attributes));
    }

    public function test_an_album_can_be_created_for_an_edition(): void
    {
        $response = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums", [
            'name' => 'พิธีเปิด',
            'description' => 'ภาพบรรยากาศพิธีเปิด',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, SportsAlbum::count());
        $album = SportsAlbum::first();
        $this->assertEquals('พิธีเปิด', $album->name);
        $this->assertEquals($this->edition->id, $album->edition_id);
        $this->assertEquals($this->academy->id, $album->academy_id);
        $this->assertEquals($this->actor->id, $album->created_by_user_id);
    }

    public function test_a_discipline_from_another_edition_is_rejected(): void
    {
        $edition2 = $this->makeEdition($this->year, 'draft');
        $discipline2 = $this->makeDiscipline(['edition_id' => $edition2->id]);

        $response = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums", [
            'name' => 'ภาพแข่ง',
            'discipline_id' => $discipline2->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, SportsAlbum::count());
    }

    public function test_a_house_outside_the_edition_is_rejected(): void
    {
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);

        $response = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums", [
            'name' => 'ภาพคณะ',
            'house_group_id' => $extraHouse->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, SportsAlbum::count());
    }

    public function test_uploading_photos_stores_files_and_rows(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file1 = UploadedFile::fake()->image('pic1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('pic2.jpg', 800, 600);

        $response = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file1, $file2],
            'captions' => ['รูปแรก', 'รูปสอง'],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, SportsPhoto::count());

        $photos = SportsPhoto::orderBy('display_order')->get();
        $this->assertEquals('รูปแรก', $photos[0]->caption);
        $this->assertEquals('รูปสอง', $photos[1]->caption);

        foreach ($photos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
            Storage::disk('public')->assertExists($photo->thumbnail_path);
        }
    }

    public function test_an_uploaded_photo_is_scaled_down_to_2048(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->image('run.jpg', 3000, 1500);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $photo = SportsPhoto::first();
        $this->assertEquals(2048, $photo->width);
        $this->assertEquals(1024, $photo->height);
        $this->assertEquals('image/jpeg', $photo->mime_type);
    }

    public function test_the_first_uploaded_photo_becomes_the_album_cover(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $this->assertNull($album->cover_photo_id);

        $file = UploadedFile::fake()->image('pic.jpg', 800, 600);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $album->refresh();
        $photo = SportsPhoto::first();
        $this->assertEquals($photo->id, $album->cover_photo_id);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->create('note.pdf', 100, 'application/pdf');

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(422);

        $this->assertEquals(0, SportsPhoto::count());
    }

    public function test_deleting_a_photo_removes_its_files(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->image('pic.jpg', 800, 600);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $photo = SportsPhoto::first();
        $path = $photo->path;
        $thumbnailPath = $photo->thumbnail_path;

        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->actor, 'api')->deleteJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/photos/{$photo->id}")
            ->assertStatus(204);

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumbnailPath);
        $this->assertEquals(0, SportsPhoto::count());
    }

    public function test_deleting_the_cover_photo_promotes_another_photo(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file1 = UploadedFile::fake()->image('pic1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('pic2.jpg', 800, 600);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file1, $file2],
        ])->assertStatus(201);

        $album->refresh();
        $photos = SportsPhoto::orderBy('display_order')->get();
        $this->assertEquals($photos[0]->id, $album->cover_photo_id);

        $this->actingAs($this->actor, 'api')->deleteJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/photos/{$photos[0]->id}")
            ->assertStatus(204);

        $album->refresh();
        $this->assertEquals($photos[1]->id, $album->cover_photo_id);
    }

    public function test_deleting_an_album_removes_its_photos_and_files(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->image('pic.jpg', 800, 600);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $photo = SportsPhoto::first();
        $path = $photo->path;

        $this->actingAs($this->actor, 'api')->deleteJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}")
            ->assertStatus(204);

        Storage::disk('public')->assertMissing($path);
        $this->assertEquals(0, SportsPhoto::count());
        $this->assertEquals(0, SportsAlbum::count());
    }

    public function test_photos_carry_a_public_url(): void
    {
        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->image('pic.jpg', 800, 600);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $response = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album->id}/photos");

        $response->assertStatus(200);
        $photoData = $response->json()[0];

        $this->assertNotEmpty($photoData['url']);
        $this->assertNotEmpty($photoData['thumbnail_url']);
    }

    public function test_an_album_from_another_academy_returns_404(): void
    {
        $owner2 = User::factory()->create();
        $academy2 = Academy::factory()->create(['user_id' => $owner2->id]);

        $album = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test',
            'created_by_user_id' => $this->actor->id,
        ]);

        $this->actingAs($owner2, 'api')->getJson("/api/academies/{$academy2->id}/sports-editions/{$this->edition->id}/albums/{$album->id}")
            ->assertStatus(404);
    }

    public function test_setting_a_cover_from_another_album_is_rejected(): void
    {
        $album1 = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test 1',
            'created_by_user_id' => $this->actor->id,
        ]);

        $album2 = SportsAlbum::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'Test 2',
            'created_by_user_id' => $this->actor->id,
        ]);

        $file = UploadedFile::fake()->image('pic.jpg', 800, 600);
        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album1->id}/photos", [
            'photos' => [$file],
        ])->assertStatus(201);

        $photo1 = SportsPhoto::first();

        $response = $this->actingAs($this->actor, 'api')->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/albums/{$album2->id}", [
            'cover_photo_id' => $photo1->id,
        ]);

        $response->assertStatus(422);
    }
}
