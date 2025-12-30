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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('cover');
            $table->string('cover_header')->nullable()->after('logo');
            $table->text('cover_subheader')->nullable()->after('cover_header');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['logo', 'cover_header', 'cover_subheader']);
        });
    }
};
