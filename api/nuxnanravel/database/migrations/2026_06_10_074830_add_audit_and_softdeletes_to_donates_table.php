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
        Schema::table('donates', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donates', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['reviewed_at', 'review_note']);
            $table->tinyInteger('approved_by')->nullable()->change();
        });
    }
};
