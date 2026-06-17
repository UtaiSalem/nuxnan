<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->json('student_editable_fields')->nullable()
                ->comment('Whitelist/Blacklist of fields students can edit themselves')
                ->after('social_media_links');
            $table->string('approval_flow')->default('single')
                ->comment('single | two_level')
                ->after('student_editable_fields');
        });

        // Initialize default for existing academies
        DB::table('academies')->update([
            'student_editable_fields' => json_encode([
                'mode' => 'blacklist',
                'fields' => ['citizen_id', 'student_id', 'academic', 'health']
            ]),
            'approval_flow' => 'single'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn(['student_editable_fields', 'approval_flow']);
        });
    }
};
