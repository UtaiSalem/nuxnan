<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('location', 150)->nullable();
            $table->boolean('is_open')->default(false);
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opened_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['election_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_stations');
    }
};
