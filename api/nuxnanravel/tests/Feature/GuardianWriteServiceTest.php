<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentGuardian;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuardianWriteServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('audit_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action');
            $t->string('entity_type');
            $t->unsignedBigInteger('entity_id');
            $t->string('module')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('metadata')->nullable();
            $t->string('ip_address')->nullable();
            $t->string('user_agent')->nullable();
            $t->text('url')->nullable();
            $t->string('method')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('students', fn ($t) => [$t->id(), $t->unsignedBigInteger('academy_id')->nullable(), $t->string('student_id')->nullable()]);
        Schema::create('student_guardians', function ($t) {
            $t->id();
            $t->unsignedBigInteger('academy_id')->nullable();
            $t->unsignedBigInteger('student_id');
            $t->string('student_code')->nullable();
            foreach (['guardian_type', 'citizen_id', 'title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'relationship', 'status', 'nationality'] as $f) {
                $t->string($f)->nullable();
            } $t->decimal('monthly_income', 10, 2)->nullable();
            $t->boolean('is_primary_contact')->default(false);
            $t->boolean('is_emergency_contact')->default(false);
            $t->timestamps();
        });
        Schema::create('guardians', function ($t) {
            $t->id();
            $t->unsignedBigInteger('academy_id')->nullable();
            $t->string('citizen_id')->nullable();
            foreach (['title_prefix', 'first_name', 'last_name', 'occupation', 'workplace', 'status', 'nationality'] as $f) {
                $t->string($f)->nullable();
            } $t->decimal('monthly_income', 10, 2)->nullable();
            $t->json('legacy_row_ids')->nullable();
            $t->timestamps();
        });
        Schema::create('student_guardian_links', function ($t) {
            $t->id();
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('guardian_id');
            foreach (['guardian_type', 'relationship', 'appointed_by_role'] as $f) {
                $t->string($f)->nullable();
            } $t->boolean('is_primary_contact')->default(false);
            $t->boolean('is_emergency_contact')->default(false);
            $t->unsignedBigInteger('appointed_by_user_id')->nullable();
            $t->timestamp('appointed_at')->nullable();
            $t->json('legacy_row_ids')->nullable();
            $t->timestamps();
        });
        Schema::create('guardian_contacts', function ($t) {
            $t->id();
            $t->unsignedBigInteger('guardian_id');
            $t->unsignedBigInteger('guardian_person_id')->nullable();
            $t->string('contact_type');
            $t->string('contact_value');
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['guardian_contacts', 'student_guardian_links', 'guardians', 'student_guardians', 'students', 'audit_logs'] as $t) {
            Schema::dropIfExists($t);
        } parent::tearDown();
    }

    private function student(int $id = 1): Student
    {
        DB::table('students')->insert(['id' => $id, 'academy_id' => 1, 'student_id' => 'S'.$id]);

        return Student::find($id);
    }

    private function data(array $x = []): array
    {
        return array_replace(['first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123', 'guardian_type' => 'father', 'relationship' => 'father', 'status' => 'alive'], $x);
    }

    private function create(array $x = [], ?Student $s = null): StudentGuardian
    {
        return app(GuardianWriteService::class)->create($s ?: $this->student(), $this->data($x));
    }

    public function test_create_writes_legacy_person_link_and_legacy_id(): void
    {
        $g = $this->create();
        $this->assertDatabaseHas('student_guardians', ['id' => $g->id]);
        $this->assertDatabaseHas('guardians', ['id' => DB::table('student_guardian_links')->value('guardian_id')]);
        $this->assertContains($g->id, DB::table('student_guardian_links')->value('legacy_row_ids') ? json_decode(DB::table('student_guardian_links')->value('legacy_row_ids'), true) : []);
    }

    public function test_matching_citizen_and_name_reuses_person(): void
    {
        $g = $this->create();
        $count = DB::table('guardians')->count();
        $g2 = $this->create([], $this->student(2));
        $this->assertSame($count, DB::table('guardians')->count());
        $this->assertCount(2, DB::table('student_guardian_links')->get());
    }

    public function test_matching_citizen_different_name_creates_person(): void
    {
        $this->create();
        $this->create(['first_name' => 'Different'], $this->student(2));
        $this->assertSame(2, DB::table('guardians')->count());
    }

    public function test_update_writes_both_sides(): void
    {
        $g = $this->create();
        app(GuardianWriteService::class)->update($g, $this->data(['first_name' => 'Updated', 'relationship' => 'mother']));
        $this->assertDatabaseHas('student_guardians', ['id' => $g->id, 'first_name' => 'Updated']);
        $this->assertDatabaseHas('guardians', ['first_name' => 'Updated']);
        $this->assertDatabaseHas('student_guardian_links', ['relationship' => 'mother']);
    }

    public function test_delete_orphan_person(): void
    {
        $g = $this->create();
        $pid = DB::table('student_guardian_links')->value('guardian_id');
        app(GuardianWriteService::class)->delete($g);
        $this->assertDatabaseMissing('guardians', ['id' => $pid]);
    }

    public function test_delete_keeps_shared_person_and_other_link(): void
    {
        $g = $this->create();
        $g2 = $this->create([], $this->student(2));
        $pid = DB::table('student_guardian_links')->value('guardian_id');
        app(GuardianWriteService::class)->delete($g);
        $this->assertDatabaseHas('guardians', ['id' => $pid]);
        $this->assertDatabaseHas('student_guardian_links', ['guardian_id' => $pid, 'student_id' => 2]);
    }

    public function test_phone_contact_has_both_ids(): void
    {
        $g = $this->create(['phone' => '0812345678']);
        $this->assertNotNull(DB::table('guardian_contacts')->where('guardian_id', $g->id)->value('guardian_person_id'));
    }

    public function test_status_is_alive(): void
    {
        $g = $this->create();
        $pid = DB::table('student_guardian_links')->value('guardian_id');
        $this->assertSame('alive', DB::table('student_guardians')->where('id', $g->id)->value('status'));
        $this->assertSame('alive', DB::table('guardians')->where('id', $pid)->value('status'));
    }

    public function test_guardian_type_is_optional(): void
    {
        $g = $this->create(['guardian_type' => null]);
        $this->assertNull($g->guardian_type);
    }
}
