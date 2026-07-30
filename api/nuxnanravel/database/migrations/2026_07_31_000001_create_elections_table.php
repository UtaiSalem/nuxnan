<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'nomination', 'campaign', 'voting', 'closed', 'published', 'cancelled'])->default('draft');
            $table->dateTime('nomination_opens_at')->nullable();
            $table->dateTime('nomination_closes_at')->nullable();
            $table->dateTime('voting_opens_at')->nullable();
            $table->dateTime('voting_closes_at')->nullable();
            $table->boolean('allow_abstain')->default(true);
            $table->unsignedInteger('ballot_ttl_seconds')->default(180);
            $table->dateTime('voter_roll_locked_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['academy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
