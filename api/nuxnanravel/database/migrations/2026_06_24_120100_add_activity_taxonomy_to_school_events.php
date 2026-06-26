<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_events', function (Blueprint $table) {
            $table->string('attendance_pattern')->default('one_time')->after('event_type');
            $table->foreignId('group_id')->nullable()->after('academy_id')->constrained('academy_groups')->nullOnDelete();
        });

        Schema::table('activity_sessions', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->after('status');
            $table->timestamp('qr_token_expires_at')->nullable()->after('qr_token');
            $table->string('slot_label')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('school_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn('attendance_pattern');
        });

        Schema::table('activity_sessions', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_token_expires_at', 'slot_label']);
        });
    }
};
