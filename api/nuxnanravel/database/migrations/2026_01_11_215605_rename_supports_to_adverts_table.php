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
        Schema::rename('supports', 'adverts');
        Schema::rename('support_viewers', 'advert_viewers');
        
        Schema::table('advert_viewers', function (Blueprint $table) {
            $table->renameColumn('support_id', 'advert_id');
        });

        \DB::table('activities')->where('activityable_type', 'App\Models\Support')->update(['activityable_type' => 'App\Models\Advert']);
        \DB::table('activities')->where('activityable_type', 'App\Models\SupportViewer')->update(['activityable_type' => 'App\Models\AdvertViewer']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advert_viewers', function (Blueprint $table) {
            $table->renameColumn('advert_id', 'support_id');
        });

        Schema::rename('advert_viewers', 'support_viewers');
        Schema::rename('adverts', 'supports');

        \DB::table('activities')->where('activityable_type', 'App\Models\Advert')->update(['activityable_type' => 'App\Models\Support']);
        \DB::table('activities')->where('activityable_type', 'App\Models\AdvertViewer')->update(['activityable_type' => 'App\Models\SupportViewer']);
    }
};
