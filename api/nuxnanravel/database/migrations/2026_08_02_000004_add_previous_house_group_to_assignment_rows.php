<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('house_assignment_rows', function (Blueprint $table) {
            $table->foreignId('previous_house_group_id')->nullable()->after('house_group_id')->constrained('academy_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // dropForeign() returns a Fluent, not the Blueprint — chaining dropColumn onto it
        // is swallowed by Fluent::__call and the column survives the rollback.
        Schema::table('house_assignment_rows', function (Blueprint $table) {
            $table->dropForeign(['previous_house_group_id']);
            $table->dropColumn('previous_house_group_id');
        });
    }
};
