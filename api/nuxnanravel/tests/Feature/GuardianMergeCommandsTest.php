<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuardianMergeCommandsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('guardians', function ($t) {
            $t->id();
            foreach (['academy_id', 'user_id'] as $f) {
                $t->unsignedBigInteger($f)->nullable();
            } $t->string('citizen_id')->nullable();
            foreach (['title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'nationality', 'status'] as $f) {
                $t->string($f)->nullable();
            } $t->decimal('monthly_income', 10, 2)->nullable();
            $t->json('legacy_row_ids')->nullable();
            $t->timestamps();
        });
        Schema::create('guardian_merge_candidates', function ($t) {
            $t->id();
            $t->unsignedBigInteger('academy_id')->nullable();
            $t->string('reason');
            $t->string('group_key');
            $t->json('guardian_ids');
            $t->unsignedInteger('record_count');
            $t->string('status');
            $t->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('note')->nullable();
            $t->json('absorbed_snapshot')->nullable();
            $t->timestamps();
        });
        Schema::create('students', fn ($t) => $t->id());
        Schema::create('student_guardian_links', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('guardian_id');
            $t->string('guardian_type')->nullable();
            $t->string('relationship')->nullable();
            $t->boolean('is_primary_contact');
            $t->boolean('is_emergency_contact');
            $t->timestamp('appointed_at')->nullable();
            $t->timestamps();
            $t->json('legacy_row_ids')->nullable();
        });
        Schema::create('guardian_contacts', function ($t) {
            $t->id();
            $t->unsignedBigInteger('guardian_person_id')->nullable();
            $t->string('contact_type');
            $t->string('contact_value');
            $t->boolean('is_primary');
            $t->boolean('is_verified');
            $t->unsignedBigInteger('superseded_by_contact_id')->nullable();
            $t->timestamps();
        });
        DB::table('students')->insert(['id' => 1]);
    }

    protected function tearDown(): void
    {
        foreach (['guardian_contacts', 'student_guardian_links', 'students', 'guardian_merge_candidates', 'guardians'] as $t) {
            Schema::dropIfExists($t);
        } parent::tearDown();
    }

    private function guardians(): array
    {
        $now = now();
        $a = DB::table('guardians')->insertGetId(['first_name' => 'A', 'last_name' => 'A', 'status' => 'alive', 'nationality' => 'ไทย', 'created_at' => $now, 'updated_at' => $now]);
        $b = DB::table('guardians')->insertGetId(['first_name' => 'B', 'last_name' => 'B', 'status' => 'alive', 'nationality' => 'ไทย', 'created_at' => $now, 'updated_at' => $now]);
        $candidate = DB::table('guardian_merge_candidates')->insertGetId(['reason' => 'same_name_diff_citizen', 'group_key' => 'test-'.$a, 'guardian_ids' => json_encode([$a, $b]), 'record_count' => 2, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);

        return [$a, $b, $candidate];
    }

    public function test_merge_moves_and_collapses_links(): void
    {
        [$keep,$absorb,$candidate] = $this->guardians();
        $student = DB::table('students')->value('id');
        $now = now();
        DB::table('student_guardian_links')->insert([['student_id' => $student, 'guardian_id' => $keep, 'guardian_type' => 'guardian', 'relationship' => 'other', 'is_primary_contact' => 0, 'is_emergency_contact' => 0, 'created_at' => $now, 'updated_at' => $now], ['student_id' => $student, 'guardian_id' => $absorb, 'guardian_type' => 'father', 'relationship' => 'father', 'is_primary_contact' => 1, 'is_emergency_contact' => 1, 'created_at' => $now, 'updated_at' => $now]]);
        $this->artisan('guardians:merge', ['--candidate' => $candidate, '--keep' => $keep])->assertExitCode(0);
        $this->assertDatabaseCount('student_guardian_links', 1);
        $this->assertDatabaseHas('student_guardian_links', ['guardian_id' => $keep, 'is_primary_contact' => 1, 'is_emergency_contact' => 1, 'guardian_type' => 'father']);
        $this->assertDatabaseMissing('guardians', ['id' => $absorb]);
    }

    public function test_merge_collapses_multiple_absorbed_links_without_losing_legacy_ids(): void
    {
        [$keep, $absorb, $candidate] = $this->guardians();
        $secondAbsorb = DB::table('guardians')->insertGetId(['first_name' => 'C', 'last_name' => 'C', 'status' => 'alive', 'nationality' => 'ไทย', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('guardian_merge_candidates')->where('id', $candidate)->update(['guardian_ids' => json_encode([$keep, $absorb, $secondAbsorb]), 'record_count' => 3]);
        $now = now();
        DB::table('student_guardian_links')->insert([
            ['student_id' => 1, 'guardian_id' => $absorb, 'guardian_type' => 'guardian', 'relationship' => 'other', 'is_primary_contact' => 0, 'is_emergency_contact' => 0, 'legacy_row_ids' => json_encode([100]), 'created_at' => $now, 'updated_at' => $now],
            ['student_id' => 1, 'guardian_id' => $secondAbsorb, 'guardian_type' => 'father', 'relationship' => 'father', 'is_primary_contact' => 1, 'is_emergency_contact' => 0, 'legacy_row_ids' => json_encode([101]), 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->artisan('guardians:merge', ['--candidate' => $candidate, '--keep' => $keep])->assertExitCode(0);
        $this->assertDatabaseCount('student_guardian_links', 1);
        $link = DB::table('student_guardian_links')->where('guardian_id', $keep)->first();
        $this->assertSame([100, 101], json_decode($link->legacy_row_ids, true));
        $this->assertSame(1, (int) $link->is_primary_contact);
    }

    public function test_merge_moves_and_supersedes_contacts(): void
    {
        [$keep,$absorb,$candidate] = $this->guardians();
        $now = now();
        DB::table('guardian_contacts')->insert([['guardian_person_id' => $keep, 'contact_type' => 'phone', 'contact_value' => '0800000000', 'is_primary' => 1, 'is_verified' => 0, 'created_at' => $now, 'updated_at' => $now], ['guardian_person_id' => $absorb, 'contact_type' => 'phone', 'contact_value' => '0800000000', 'is_primary' => 0, 'is_verified' => 1, 'created_at' => $now, 'updated_at' => $now]]);
        $this->artisan('guardians:merge', ['--candidate' => $candidate, '--keep' => $keep])->assertExitCode(0);
        $this->assertDatabaseHas('guardian_contacts', ['guardian_person_id' => $keep, 'superseded_by_contact_id' => null]);
        $this->assertDatabaseCount('guardian_contacts', 2);
        $this->assertDatabaseHas('guardian_merge_candidates', ['id' => $candidate, 'status' => 'merged']);
    }

    public function test_reject_is_preserved_by_scan(): void
    {
        [$keep,$absorb,$candidate] = $this->guardians();
        $this->artisan('guardians:reject-merge-candidate', ['--candidate' => $candidate, '--note' => 'verified distinct'])->assertExitCode(0);
        $this->artisan('guardians:scan-merge-candidates')->assertExitCode(0);
        $this->assertDatabaseHas('guardian_merge_candidates', ['id' => $candidate, 'status' => 'rejected', 'note' => 'verified distinct']);
    }
}
