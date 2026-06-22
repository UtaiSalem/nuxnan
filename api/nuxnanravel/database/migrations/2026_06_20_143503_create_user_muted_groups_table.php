<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_muted_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('academy_group_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'academy_group_id']);
            $table->index('academy_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_muted_groups');
    }
};
