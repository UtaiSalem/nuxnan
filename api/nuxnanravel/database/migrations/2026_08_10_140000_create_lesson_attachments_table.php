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
        if (! Schema::hasTable('lesson_attachments')) {
            Schema::create('lesson_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('attachable_type', 255);
                $table->unsignedBigInteger('attachable_id');
                $table->unsignedBigInteger('course_id')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('filename', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 150)->nullable();
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->integer('order')->default(0);
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamps();

                $table->index(['attachable_type', 'attachable_id'], 'lesson_attach_morph_idx');
                $table->index('course_id', 'lesson_attach_course_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};
