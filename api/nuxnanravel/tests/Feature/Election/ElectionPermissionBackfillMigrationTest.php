<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ElectionPermissionBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_is_a_no_op_and_keeps_backfill_table(): void
    {
        $migration = require database_path('migrations/2026_08_24_000001_backfill_election_permissions_and_member_roles.php');

        $migration->up();

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('academy_member_role_backfills'));

        $migration->down();
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('academy_member_role_backfills'));
    }

    public function test_backfill_round_trip_preserves_existing_roles_and_is_idempotent(): void
    {
        $academy = Academy::factory()->create();
        $student = AcademyRole::create(['academy_id' => $academy->id, 'name' => 'student', 'display_name_th' => 'Student', 'permissions' => []]);
        $staff = AcademyRole::create(['academy_id' => $academy->id, 'name' => 'staff', 'display_name_th' => 'Staff', 'permissions' => []]);
        $teacher = AcademyRole::create(['academy_id' => $academy->id, 'name' => 'teacher', 'display_name_th' => 'Teacher', 'permissions' => []]);

        $newMember = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 2]);
        $existingMember = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 2, 'academy_role_id' => $student->id]);
        $pendingMember = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 1]);

        $migration = require database_path('migrations/2026_08_24_000001_backfill_election_permissions_and_member_roles.php');
        $migration->up();
        $firstPermissions = DB::table('academy_roles')->pluck('permissions', 'name')->all();
        $migration->up();

        $this->assertSame($staff->id, DB::table('academy_members')->where('id', $newMember->id)->value('academy_role_id'));
        $this->assertSame($student->id, DB::table('academy_members')->where('id', $existingMember->id)->value('academy_role_id'));
        $this->assertNull(DB::table('academy_members')->where('id', $pendingMember->id)->value('academy_role_id'));
        $this->assertSame($firstPermissions, DB::table('academy_roles')->pluck('permissions', 'name')->all());
        $this->assertSame(1, DB::table('academy_member_role_backfills')->count());
        $this->assertContains('elections.station', $teacher->fresh()->permissions);

        $migration->down();
        $this->assertNull(DB::table('academy_members')->where('id', $newMember->id)->value('academy_role_id'));
        $this->assertSame($student->id, DB::table('academy_members')->where('id', $existingMember->id)->value('academy_role_id'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('academy_member_role_backfills'));
    }
}
