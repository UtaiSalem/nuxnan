<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand lessons.content and topics.content from TEXT (64KB) to MEDIUMTEXT (16MB)
     * so lessons imported from .docx files (with long Thai text) fit.
     */
    public function up(): void
    {
        // SQLite does not distinguish TEXT from MEDIUMTEXT.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->mediumText('content')->nullable()->change();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->mediumText('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (['lessons', 'topics'] as $tableName) {
            if (DB::table($tableName)->whereRaw('LENGTH(content) > 65535')->exists()) {
                throw new RuntimeException(
                    "Cannot shrink {$tableName}.content back to TEXT: some rows contain more than 65535 bytes. "
                    .'Shorten or move that content first; this migration will not truncate data.'
                );
            }
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });
    }
};
