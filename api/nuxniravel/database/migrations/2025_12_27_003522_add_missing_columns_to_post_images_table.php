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
        Schema::table('post_images', function (Blueprint $table) {
            // Add thumbnail column after filename if not exists
            if (!Schema::hasColumn('post_images', 'thumbnail')) {
                $table->string('thumbnail')->nullable()->after('filename');
            }
            // Add size column if not exists
            if (!Schema::hasColumn('post_images', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('order');
            }
            // Add mime_type column if not exists
            if (!Schema::hasColumn('post_images', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'size', 'mime_type']);
        });
    }
};
