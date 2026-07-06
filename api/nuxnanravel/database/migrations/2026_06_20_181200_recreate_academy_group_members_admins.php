<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasMembers = false;
        $hasAdmins = false;

        try {
            $hasMembers = Schema::hasTable('academy_group_members') && DB::table('academy_group_members')->count() > 0;
            $hasAdmins = Schema::hasTable('academy_group_admins') && DB::table('academy_group_admins')->count() > 0;
        } catch (Exception $e) {
            // Table might not exist or columns might be invalid
        }

        if ($hasMembers || $hasAdmins) {
            // Safely alter existing tables to add missing columns
            if (Schema::hasTable('academy_group_members')) {
                Schema::table('academy_group_members', function (Blueprint $table) {
                    if (! Schema::hasColumn('academy_group_members', 'academy_group_id')) {
                        $table->foreignId('academy_group_id')->nullable()->constrained()->cascadeOnDelete();
                    }
                    if (! Schema::hasColumn('academy_group_members', 'user_id')) {
                        $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                    }
                    if (! Schema::hasColumn('academy_group_members', 'role')) {
                        $table->string('role')->default('student');
                    }
                    if (! Schema::hasColumn('academy_group_members', 'status')) {
                        $table->tinyInteger('status')->default(2); // 1 = pending, 2 = approved
                    }
                    if (! Schema::hasColumn('academy_group_members', 'invited_by')) {
                        $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                    }
                });
            }

            if (Schema::hasTable('academy_group_admins')) {
                Schema::table('academy_group_admins', function (Blueprint $table) {
                    if (! Schema::hasColumn('academy_group_admins', 'academy_group_id')) {
                        $table->foreignId('academy_group_id')->nullable()->constrained()->cascadeOnDelete();
                    }
                    if (! Schema::hasColumn('academy_group_admins', 'user_id')) {
                        $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                    }
                    if (! Schema::hasColumn('academy_group_admins', 'role')) {
                        $table->string('role')->default('leader');
                    }
                    if (! Schema::hasColumn('academy_group_admins', 'appointed_by')) {
                        $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
                    }
                });
            }
        } else {
            // Clean drop and recreate
            Schema::dropIfExists('academy_group_members');
            Schema::create('academy_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role')->default('student');
                $table->tinyInteger('status')->default(2); // 1 = pending, 2 = approved
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['academy_group_id', 'user_id']);
                $table->index('user_id');
                $table->index('status');
            });

            Schema::dropIfExists('academy_group_admins');
            Schema::create('academy_group_admins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role')->default('leader');
                $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['academy_group_id', 'user_id']);
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_group_members');
        Schema::dropIfExists('academy_group_admins');

        Schema::create('academy_group_members', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('academy_group_admins', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
