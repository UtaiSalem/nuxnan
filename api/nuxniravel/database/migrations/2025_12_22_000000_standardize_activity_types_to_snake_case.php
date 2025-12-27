<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping of legacy activity_type values to new snake_case format.
     */
    private array $activityTypeMapping = [
        'createpost' => 'create_post',
        'updatepost' => 'update_post',
        'deletepost' => 'delete_post',
        'sharepost' => 'share_post',
        'createcomment' => 'create_comment',
        'updatecomment' => 'update_comment',
        'deletecomment' => 'delete_comment',
        'givedonation' => 'give_donation',
        'recieveddonation' => 'receive_donation',  // Fix typo: recieved -> receive
        'receiveddonation' => 'receive_donation',
        'createadvertise' => 'create_advertise',
        'viewadvertise' => 'view_advertise',
        'approveadvertise' => 'approve_advertise',
        'rejectadvertise' => 'reject_advertise',
    ];

    /**
     * Run the migrations.
     * 
     * Converts all legacy activity_type values to snake_case format
     * for consistency with REST API standards and frontend actionMap.
     */
    public function up(): void
    {
        foreach ($this->activityTypeMapping as $oldValue => $newValue) {
            DB::table('activities')
                ->where('activity_type', $oldValue)
                ->update(['activity_type' => $newValue]);
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Converts snake_case values back to legacy format.
     */
    public function down(): void
    {
        foreach ($this->activityTypeMapping as $oldValue => $newValue) {
            DB::table('activities')
                ->where('activity_type', $newValue)
                ->update(['activity_type' => $oldValue]);
        }
    }
};
