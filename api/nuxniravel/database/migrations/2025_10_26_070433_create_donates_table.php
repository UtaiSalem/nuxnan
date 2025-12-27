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
        Schema::create('donates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->integer('donor_id')->nullable();
            $table->string('donor_name')->nullable();
            $table->decimal('amounts', 10);
            $table->string('slip');
            $table->date('transfer_date');
            $table->time('transfer_time')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('donation_purpose')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->integer('remaining_points')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->integer('privacy_settings')->default(3);
            $table->tinyInteger('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donates');
    }
};
