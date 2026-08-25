<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guardian_contacts') || ! Schema::hasColumn('guardian_contacts', 'guardian_person_id')) {
            return;
        }

        // The imported data carries several primary contacts of the same type for one person —
        // 440 guardians with two or three "main" phone numbers. Reads that ask for the primary
        // phone then get whichever row the database happened to return first, so the number shown
        // on a student's card can change between requests.
        //
        // The set-primary endpoint enforces one primary per type going forward; this catches up
        // the rows that predate it. The lowest id wins: it is deterministic and picks the number
        // the school recorded first, rather than inventing a preference.
        $groups = DB::table('guardian_contacts')
            ->select('guardian_person_id', 'contact_type')
            ->where('is_primary', true)
            ->whereNotNull('guardian_person_id')
            ->groupBy('guardian_person_id', 'contact_type')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $keepId = DB::table('guardian_contacts')
                ->where('guardian_person_id', $group->guardian_person_id)
                ->where('contact_type', $group->contact_type)
                ->where('is_primary', true)
                ->min('id');

            DB::table('guardian_contacts')
                ->where('guardian_person_id', $group->guardian_person_id)
                ->where('contact_type', $group->contact_type)
                ->where('is_primary', true)
                ->where('id', '!=', $keepId)
                ->update(['is_primary' => false]);
        }
    }

    public function down(): void
    {
        // Deliberately empty. Which of the duplicates used to be flagged primary is not recoverable
        // from the remaining data, and restoring the extra flags would only put back the ambiguity
        // this migration exists to remove. Every contact row and its value is untouched either way —
        // only the is_primary flag moved.
    }
};
