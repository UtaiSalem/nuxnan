<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('election_parties')->nullOnDelete();
            $table->unsignedInteger('votes');
            $table->unsignedSmallInteger('rank')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->dateTime('published_at');
            $table->foreignId('published_by')->constrained('users');
            $table->timestamps();
            $table->unique(['election_id', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_results');
    }
};
