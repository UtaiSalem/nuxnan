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
        Schema::table('academy_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_groups', 'academy_id')) {
                $table->foreignId('academy_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('academy_groups', 'name')) {
                $table->string('name')->after('academy_id');
            }
            if (!Schema::hasColumn('academy_groups', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('academy_groups', 'type')) {
                $table->string('type')->default('classroom')->after('description');
            }
            if (!Schema::hasColumn('academy_groups', 'settings')) {
                $table->json('settings')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropColumn(['academy_id', 'name', 'description', 'type', 'settings']);
        });
    }
};
