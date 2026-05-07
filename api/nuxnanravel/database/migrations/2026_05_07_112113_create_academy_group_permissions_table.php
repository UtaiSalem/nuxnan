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
        Schema::create('academy_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->string('permission_key', 100);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['academy_group_id', 'permission_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_group_permissions');
    }
};
