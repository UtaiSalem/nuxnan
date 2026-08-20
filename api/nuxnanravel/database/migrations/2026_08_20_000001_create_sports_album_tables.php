<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sports_albums')) {
            return;
        }

        Schema::create('sports_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained('sports_disciplines')->nullOnDelete();
            $table->foreignId('house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('cover_photo_id')->nullable();
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['edition_id', 'created_at'], 'sa_edition_created_idx');
        });

        Schema::create('sports_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('sports_albums')->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('mime_type', 60)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['album_id', 'display_order'], 'sp_album_order_idx');
        });

        Schema::table('sports_albums', function (Blueprint $table) {
            $table->foreign('cover_photo_id', 'sa_cover_photo_fk')
                ->references('id')->on('sports_photos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sports_albums')) {
            Schema::table('sports_albums', function (Blueprint $table) {
                $table->dropForeign('sa_cover_photo_fk');
            });
        }
        Schema::dropIfExists('sports_photos');
        Schema::dropIfExists('sports_albums');
    }
};
