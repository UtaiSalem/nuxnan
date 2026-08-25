<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The four values the legacy enum was born with. */
    private const LEGACY_ENUM = ['father', 'mother', 'guardian', 'other'];

    public function up(): void
    {
        if (! Schema::hasTable('student_guardians') || ! Schema::hasColumn('student_guardians', 'guardian_type')) {
            return;
        }

        // Only MySQL enforces the enum. SQLite (tests) stores whatever it is handed, which is
        // exactly why this mismatch survived a green test suite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // The legacy column is enum('father','mother','guardian','other') while the API and the
        // student_guardian_links column both accept eight relationship types. Under STRICT mode
        // MySQL rejects the other four outright — appointing an uncle died on the dual-write with
        // "Data truncated for column 'guardian_type'". G3 always planned for varchar here; the
        // move just never happened because nothing wrote the wider values until G-S10.
        DB::statement('ALTER TABLE `student_guardians` MODIFY `guardian_type` VARCHAR(50) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_guardians') || ! Schema::hasColumn('student_guardians', 'guardian_type')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Anything outside the original four cannot survive the trip back, so blank it first
        // rather than letting the conversion truncate rows on the way down.
        DB::table('student_guardians')
            ->whereNotNull('guardian_type')
            ->whereNotIn('guardian_type', self::LEGACY_ENUM)
            ->update(['guardian_type' => null]);

        // Restored nullable on purpose: the column was NOT NULL originally, but rows written
        // after this migration may legitimately carry no type at all (D6 makes it optional),
        // and inventing a value for them to satisfy NOT NULL would be worse than allowing null.
        DB::statement("ALTER TABLE `student_guardians` MODIFY `guardian_type` ENUM('father','mother','guardian','other') NULL");
    }
};
