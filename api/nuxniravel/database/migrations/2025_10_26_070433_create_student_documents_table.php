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
        Schema::create('student_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id')->index('student_documents_student_id_foreign');
            $table->enum('document_type', ['profile_image', 'birth_certificate', 'house_registration', 'parent_id', 'transcript', 'medical_certificate', 'other'])->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->unsignedBigInteger('uploaded_by')->nullable()->index('student_documents_uploaded_by_foreign');
            $table->boolean('is_verified')->default(false)->index();
            $table->unsignedBigInteger('verified_by')->nullable()->index('student_documents_verified_by_foreign');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
