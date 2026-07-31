<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('election_results', function (Blueprint $table) {
            $table->dateTime('published_at')->nullable()->change();
            $table->foreignId('published_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('election_results', function (Blueprint $table) {
            $table->dateTime('published_at')->nullable(false)->change();
            $table->foreignId('published_by')->nullable(false)->change();
        });
    }
};
