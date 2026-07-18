<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('revenue_share_policies')->where('scope_type', 'platform')->whereNull('scope_id')->exists()) {
            DB::table('revenue_share_policies')->insert([
                'scope_type' => 'platform', 'scope_id' => null,
                'student_pct' => 70.00, 'course_pct' => 20.00, 'platform_pct' => 10.00,
                'effective_from' => now(), 'version' => 1,
                'notes' => 'Default platform revenue share',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('revenue_share_policies')->where('scope_type', 'platform')->whereNull('scope_id')->where('version', 1)->delete();
    }
};
