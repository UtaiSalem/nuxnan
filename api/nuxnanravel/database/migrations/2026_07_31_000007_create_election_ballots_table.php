<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_ballots', function (Blueprint $table) {
            $table->char('uuid', 36);
            $table->primary('uuid');
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('election_parties')->restrictOnDelete();
            $table->index('election_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_ballots');
    }
};
