<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['academy_posts', 'school_announcements'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('scope_type')->default('academy')->index();
                $blueprint->unsignedBigInteger('scope_id')->nullable()->index();
            });
        }

        Schema::create('academy_scope_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('academy_scope_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_scope_files');
        Schema::dropIfExists('academy_scope_tasks');
        foreach (['academy_posts', 'school_announcements'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['scope_type', 'scope_id']);
            });
        }
    }
};
