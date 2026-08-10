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
        if (! Schema::hasTable('assignment_answer_attachments')) {
            Schema::create('assignment_answer_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('assignment_answer_id');
                $table->unsignedBigInteger('assignment_id')->nullable();
                $table->unsignedBigInteger('course_id')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('filename', 255);          // stored uuid name
                $table->string('original_name', 255);     // as uploaded, Thai must survive
                $table->string('mime_type', 150)->nullable();
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->integer('order')->default(0);
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamps();

                $table->index('assignment_answer_id', 'asm_ans_attach_answer_idx');
                $table->index('course_id', 'asm_ans_attach_course_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_answer_attachments');
    }
};
