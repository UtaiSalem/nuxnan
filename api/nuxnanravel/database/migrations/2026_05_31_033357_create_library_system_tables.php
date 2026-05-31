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
        // Books Catalog
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->integer('published_year')->nullable();
            $table->string('category')->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->string('location')->nullable(); // shelf location
            $table->string('cover_image')->nullable();
            $table->enum('status', ['available', 'archived', 'lost'])->default('available');
            $table->timestamps();
            
            $table->index(['academy_id', 'title']);
            $table->index('isbn');
        });

        // Borrowing Records
        Schema::create('library_borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // borrower
            $table->dateTime('borrowed_at');
            $table->dateTime('due_at');
            $table->dateTime('returned_at')->nullable();
            $table->enum('status', ['borrowed', 'returned', 'overdue', 'lost'])->default('borrowed');
            $table->decimal('fine_amount', 10, 2)->default(0.00);
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->constrained('users'); // staff who processed
            $table->timestamps();
            
            $table->index(['academy_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_borrowings');
        Schema::dropIfExists('library_books');
    }
};
