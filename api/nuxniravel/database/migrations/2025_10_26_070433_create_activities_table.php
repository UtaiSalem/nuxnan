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
        Schema::create('activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('activities_user_id_foreign');
            $table->string('activityable_type');
            $table->bigInteger('activityable_id');
            $table->string('activity_type');
            $table->text('action')->nullable();
            $table->text('activity_details')->nullable();
            $table->timestamps();

            $table->index(['activityable_type', 'activityable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
