<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imported production dumps already carry this column. Guard so `migrate` stays runnable.
        if (Schema::hasColumn('guardian_contacts', 'guardian_person_id')) {
            return;
        }

        Schema::table('guardian_contacts', fn (Blueprint $table) => $table->unsignedBigInteger('guardian_person_id')->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('guardian_contacts', fn (Blueprint $table) => $table->dropColumn('guardian_person_id'));
    }
};
