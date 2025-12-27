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
        Schema::create('home_visit_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('home_visit_id')->index('home_visit_participants_home_visit_id_role_index');
            $table->string('participant_name')->comment('ชื่อผู้เข้าร่วม');
            $table->text('notes')->nullable()->comment('บันทึกเพิ่มเติมของผู้เข้าร่วม');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_visit_participants');
    }
};
