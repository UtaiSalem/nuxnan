<?php

use App\Models\CourseMember;
use App\Services\LearnerIdentityService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $identityService = app(LearnerIdentityService::class);

        CourseMember::where(function ($query) {
            $query->whereNull('member_name')
                ->orWhereNull('member_code')
                ->orWhereNull('order_number');
        })->chunk(100, function ($members) use ($identityService) {
            foreach ($members as $member) {
                // Ensure relationships are loaded
                $member->loadMissing('user', 'course');

                if (! $member->user || ! $member->course) {
                    continue;
                }

                $identityService->autoPopulate($member);
                $member->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as it's a data backfill
    }
};
