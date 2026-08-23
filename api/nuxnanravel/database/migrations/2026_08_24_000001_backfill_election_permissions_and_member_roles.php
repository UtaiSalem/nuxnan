<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'director' => ['elections.view', 'elections.manage', 'elections.station'],
        'admin' => ['elections.view', 'elections.manage', 'elections.station'],
        'teacher' => ['elections.view', 'elections.station'],
        'staff' => ['elections.view'],
        'finance_staff' => ['elections.view'],
        'registrar' => ['elections.view'],
        'student' => ['elections.view'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('academy_member_role_backfills')) {
            Schema::create('academy_member_role_backfills', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('academy_member_id');
                $table->unsignedBigInteger('previous_role_id')->nullable();
                $table->unsignedBigInteger('assigned_role_id');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('academy_member_id', 'amrb_member_fk')
                    ->references('id')->on('academy_members')->cascadeOnDelete();
                $table->foreign('previous_role_id', 'amrb_prev_role_fk')
                    ->references('id')->on('academy_roles')->nullOnDelete();
                $table->foreign('assigned_role_id', 'amrb_assigned_role_fk')
                    ->references('id')->on('academy_roles')->cascadeOnDelete();
                $table->index('academy_member_id', 'amrb_member_idx');
            });
        }

        try {
            DB::transaction(function (): void {
                foreach (self::PERMISSIONS as $name => $permissions) {
                    DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                        function (object $role) use ($permissions): void {
                            $current = json_decode($role->permissions ?? '[]', true);
                            $current = is_array($current) ? $current : [];

                            DB::table('academy_roles')->where('id', $role->id)->update([
                                'permissions' => json_encode(array_values(array_unique([
                                    ...$current,
                                    ...$permissions,
                                ])), JSON_THROW_ON_ERROR),
                            ]);
                        }
                    );
                }

                $roles = DB::table('academy_roles')
                    ->whereIn('name', ['student', 'staff'])
                    ->get(['id', 'academy_id', 'name']);

                DB::table('academy_members')
                    ->where('status', 2)
                    ->whereNull('academy_role_id')
                    ->whereNotNull('user_id')
                    ->orderBy('id')
                    ->chunkById(500, function ($members) use ($roles): void {
                        $backfills = [];
                        $updates = ['student' => [], 'staff' => []];

                        foreach ($members as $member) {
                            $roleName = $member->student_id !== null ? 'student' : 'staff';
                            $role = $roles->first(fn (object $candidate): bool => $candidate->name === $roleName && $candidate->academy_id === $member->academy_id
                            ) ?? $roles->first(fn (object $candidate): bool => $candidate->name === $roleName && $candidate->academy_id === null
                            );

                            if ($role === null) {
                                throw new RuntimeException("Required {$roleName} role for academy {$member->academy_id} was not found.");
                            }

                            $backfills[] = [
                                'academy_member_id' => $member->id,
                                'previous_role_id' => null,
                                'assigned_role_id' => $role->id,
                                'created_at' => now(),
                            ];
                            $updates[$roleName][] = $member->id;
                        }

                        if ($backfills !== []) {
                            DB::table('academy_member_role_backfills')->insert($backfills);
                        }

                        foreach ($updates as $roleName => $memberIds) {
                            if ($memberIds !== []) {
                                foreach ($members->whereIn('id', $memberIds)->groupBy('academy_id') as $academyId => $academyMembers) {
                                    $assignedRole = $roles->first(fn (object $candidate): bool => $candidate->name === $roleName && ((string) $candidate->academy_id === (string) $academyId || $candidate->academy_id === null)
                                    );
                                    foreach ($academyMembers->pluck('id')->chunk(500) as $ids) {
                                        DB::table('academy_members')->whereIn('id', $ids)->update(['academy_role_id' => $assignedRole->id]);
                                    }
                                }
                            }
                        }
                    });
            });
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            if (Schema::hasTable('academy_member_role_backfills')) {
                DB::table('academy_member_role_backfills')
                    ->orderBy('id')
                    ->chunkById(500, function ($backfills): void {
                        foreach ($backfills->groupBy('previous_role_id') as $previousRoleId => $rows) {
                            DB::table('academy_members')
                                ->whereIn('id', $rows->pluck('academy_member_id'))
                                ->update(['academy_role_id' => $previousRoleId === '' || $previousRoleId === null ? null : $previousRoleId]);
                        }
                    });
            }

            foreach (array_keys(self::PERMISSIONS) as $name) {
                DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                    function (object $role): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];
                        if (in_array('*', $permissions, true)) {
                            return;
                        }
                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_filter(
                                $permissions,
                                fn (mixed $permission): bool => ! str_starts_with((string) $permission, 'elections.')
                            )), JSON_THROW_ON_ERROR),
                        ]);
                    }
                );
            }

        });

        Schema::dropIfExists('academy_member_role_backfills');
    }
};
