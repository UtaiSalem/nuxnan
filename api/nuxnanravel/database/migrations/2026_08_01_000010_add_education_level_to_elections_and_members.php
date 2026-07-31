<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->unsignedTinyInteger('education_level')->nullable()->after('status');
        });

    }

    public function down(): void
    {
        Schema::table('elections', fn (Blueprint $table) => $table->dropColumn('education_level'));
    }
};
