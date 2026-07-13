<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_card_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('student_card_requests', 'reason_code')) {
                $table->string('reason_code', 32)->nullable()->after('reason');
            }
            if (! Schema::hasColumn('student_card_requests', 'requester_name')) {
                $table->string('requester_name', 255)->nullable()->after('reason_code');
            }
            if (! Schema::hasColumn('student_card_requests', 'requester_phone')) {
                $table->string('requester_phone', 50)->nullable()->after('requester_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_card_requests', function (Blueprint $table) {
            $table->dropColumn(['reason_code', 'requester_name', 'requester_phone']);
        });
    }
};
