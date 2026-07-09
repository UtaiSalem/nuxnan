<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $blueprint) {
            if (! Schema::hasColumn('academies', 'name_en')) {
                $blueprint->string('name_en')->nullable()->after('name');
            }
            if (! Schema::hasColumn('academies', 'description')) {
                $blueprint->text('description')->nullable()->after('slogan');
            }
            if (! Schema::hasColumn('academies', 'description_en')) {
                $blueprint->text('description_en')->nullable()->after('description');
            }
            if (! Schema::hasColumn('academies', 'website')) {
                $blueprint->string('website')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('academies', 'province')) {
                $blueprint->string('province')->nullable()->after('address');
            }
            if (! Schema::hasColumn('academies', 'country')) {
                $blueprint->string('country')->nullable()->default('Thailand')->after('province');
            }
            if (! Schema::hasColumn('academies', 'name_slug')) {
                $blueprint->string('name_slug')->nullable()->after('name_en')->index();
            }
        });

        Schema::table('academy_settings', function (Blueprint $blueprint) {
            if (! Schema::hasColumn('academy_settings', 'privacy')) {
                $blueprint->string('privacy')->default('public')->after('auto_accept_members');
            }
            if (! Schema::hasColumn('academy_settings', 'allow_student_registration')) {
                $blueprint->boolean('allow_student_registration')->default(true)->after('privacy');
            }
            if (! Schema::hasColumn('academy_settings', 'allow_parent_registration')) {
                $blueprint->boolean('allow_parent_registration')->default(true)->after('allow_student_registration');
            }
            if (! Schema::hasColumn('academy_settings', 'show_member_list')) {
                $blueprint->boolean('show_member_list')->default(true)->after('allow_parent_registration');
            }
            if (! Schema::hasColumn('academy_settings', 'show_course_list')) {
                $blueprint->boolean('show_course_list')->default(true)->after('show_member_list');
            }
            if (! Schema::hasColumn('academy_settings', 'join_mode')) {
                $blueprint->string('join_mode')->default('open')->after('privacy');
            }
        });

        // Backfill name_slug for existing academies
        $academies = DB::table('academies')->get();
        foreach ($academies as $academy) {
            if (empty($academy->name_slug)) {
                $slug = Str::slug($academy->name);
                // Check uniqueness
                $originalSlug = $slug;
                $counter = 1;
                while (DB::table('academies')->where('name_slug', $slug)->where('id', '!=', $academy->id)->exists()) {
                    $slug = $originalSlug.'-'.$counter;
                    $counter++;
                }
                DB::table('academies')->where('id', $academy->id)->update(['name_slug' => $slug]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $blueprint) {
            $columns = ['privacy', 'join_mode', 'allow_student_registration', 'allow_parent_registration', 'show_member_list', 'show_course_list'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('academy_settings', $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });

        Schema::table('academies', function (Blueprint $blueprint) {
            $columns = ['name_en', 'name_slug', 'description', 'description_en', 'website', 'province', 'country'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('academies', $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
