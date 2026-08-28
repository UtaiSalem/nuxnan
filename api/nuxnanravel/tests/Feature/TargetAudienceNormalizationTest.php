<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianAccountLinkService;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TargetAudienceNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '123'.rand(1000000000, 9999999999),
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $link = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $link->guardian];
    }

    protected function setupLinkedParent(Academy $academy): array
    {
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();
        $owner = User::factory()->create();

        $req = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $owner, $guardian);
        app(GuardianAccountLinkService::class)->accept($req, $parentUser);

        return [$parentUser, $student];
    }

    public function test_migration_normalizes_double_encoded_target_audience_idempotently()
    {
        $academy = Academy::factory()->create();
        $admin = User::factory()->create();

        // Setup row with double encoding via DB to bypass Eloquent casts
        $doubleEncodedId = DB::table('school_announcements')->insertGetId([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Double Encoded',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            // Double encoded JSON: "[\"all\"]"
            'target_audience' => json_encode(json_encode(['all'])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup row with correct encoding
        $correctEncodedId = DB::table('school_announcements')->insertGetId([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Correct Encoded',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            // Single encoded JSON: ["all"]
            'target_audience' => json_encode(['all']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the migration
        $migration = require base_path('database/migrations/2026_08_28_000003_fix_double_encoded_target_audience.php');
        $migration->up();

        // Read back values
        $doubleRow = DB::table('school_announcements')->where('id', $doubleEncodedId)->first();
        $correctRow = DB::table('school_announcements')->where('id', $correctEncodedId)->first();

        // Check double encoded was fixed
        $this->assertEquals(['all'], json_decode($doubleRow->target_audience, true));

        // Check correct encoded wasn't messed up
        $this->assertEquals(['all'], json_decode($correctRow->target_audience, true));

        // Run migration again to test idempotence
        $migration->up();

        $doubleRowAgain = DB::table('school_announcements')->where('id', $doubleEncodedId)->first();
        $this->assertEquals(['all'], json_decode($doubleRowAgain->target_audience, true));
    }

    public function test_parent_sees_normalized_and_singular_audience_announcements()
    {
        $academy = Academy::factory()->create();
        [$parentUser, $student] = $this->setupLinkedParent($academy);
        $admin = User::factory()->create();

        // 1. Double encoded (will be fixed by migration)
        $doubleEncodedId = DB::table('school_announcements')->insertGetId([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Double Encoded All',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            // Double encoded
            'target_audience' => json_encode(json_encode(['all'])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Correct singular parent
        $singularParentId = DB::table('school_announcements')->insertGetId([
            'academy_id' => $academy->id,
            'created_by' => $admin->id,
            'title' => 'Singular Parent',
            'content' => 'Test',
            'announcement_type' => 'general',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDay(),
            // Correct single encode
            'target_audience' => json_encode(['parent']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run migration to normalize the double encoded one
        $migration = require base_path('database/migrations/2026_08_28_000003_fix_double_encoded_target_audience.php');
        $migration->up();

        // Act
        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/parent/announcements");

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'announcements');

        $ids = collect($response->json('announcements'))->pluck('id')->toArray();
        $this->assertContains($doubleEncodedId, $ids);
        $this->assertContains($singularParentId, $ids);
    }
}
