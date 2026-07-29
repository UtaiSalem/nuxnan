<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardian_contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('superseded_by_contact_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('guardian_contacts', fn (Blueprint $table) => $table->dropColumn('superseded_by_contact_id'));
    }
};
