<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->integer('donor_id')->nullable();
            $table->string('donor_name')->nullable();
            $table->decimal('amounts')->nullable();
            $table->string('slip')->nullable();
            $table->date('transfer_date')->nullable();
            $table->string('transfer_time')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('donation_purpose')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->integer('remaining_points')->nullable();
            $table->boolean('status')->nullable();
            $table->integer('privacy_settings')->nullable();
            $table->boolean('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('donates');
    }
};
