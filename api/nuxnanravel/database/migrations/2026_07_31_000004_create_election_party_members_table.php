<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_party_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('election_parties')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('role', ['leader', 'deputy', 'secretary', 'treasurer', 'member']);
            $table->string('position_label', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['party_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_party_members');
    }
};
