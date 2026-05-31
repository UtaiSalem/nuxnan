<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('title')->default('เช็คชื่อการมาโรงเรียน');
            $table->time('start_time')->nullable();
            $table->integer('late_minutes')->default(15);
            $table->string('qr_token', 64)->unique()->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_attendances');
    }
};
