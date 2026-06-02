<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes()->after('remember_token');  // deleted_at
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->string('deletion_reason')->nullable()->after('deleted_by');
            $table->timestamp('anonymized_at')->nullable()->after('deletion_reason');

            $table->index('deleted_at');  // ป้องกัน slow query ตอน filter
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['deleted_by', 'deletion_reason', 'anonymized_at']);
        });
    }
};
