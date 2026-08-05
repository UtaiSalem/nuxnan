<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imported production dumps already carry this table while the migrations
        // table does not record it. Guard so `migrate` stays runnable.
        if (Schema::hasTable('guardian_merge_candidates')) {
            return;
        }

        Schema::create('guardian_merge_candidates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_id')->nullable()->index();
            $table->string('reason', 40);
            $table->string('group_key')->index();
            $table->json('guardian_ids');
            $table->unsignedInteger('record_count');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('note')->nullable();
            $table->json('absorbed_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['reason', 'group_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_merge_candidates');
    }
};
