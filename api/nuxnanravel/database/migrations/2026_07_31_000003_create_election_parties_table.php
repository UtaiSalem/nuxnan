<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('name', 120);
            $table->string('slogan', 200)->nullable();
            $table->string('logo_path')->nullable();
            $table->text('policy')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn'])->default('pending');
            $table->foreignId('applied_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['election_id', 'number']);
            $table->unique(['election_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_parties');
    }
};
