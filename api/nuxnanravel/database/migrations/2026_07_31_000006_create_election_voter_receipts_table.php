<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_voter_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('election_voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('station_id')->constrained('election_stations');
            $table->foreignId('issued_by')->constrained('users');
            $table->enum('status', ['issued', 'cast', 'void', 'expired'])->default('issued');
            $table->char('token_hash', 64)->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('cast_at')->nullable();
            $table->string('void_reason', 200)->nullable();
            $table->timestamps();
            $table->unique(['election_id', 'user_id']);
            $table->index(['election_id', 'status']);
            $table->index('station_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_voter_receipts');
    }
};
