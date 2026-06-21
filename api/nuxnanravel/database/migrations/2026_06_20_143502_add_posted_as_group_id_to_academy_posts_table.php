<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_posts', 'posted_as_group_id')) {
                $table->foreignId('posted_as_group_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('academy_groups')
                    ->nullOnDelete();
                $table->index('posted_as_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (Schema::hasColumn('academy_posts', 'posted_as_group_id')) {
                $table->dropForeign(['posted_as_group_id']);
                $table->dropIndex(['posted_as_group_id']);
                $table->dropColumn('posted_as_group_id');
            }
        });
    }
};
