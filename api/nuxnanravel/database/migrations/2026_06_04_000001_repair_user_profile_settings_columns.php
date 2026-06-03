<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'phone_number' => fn (Blueprint $table) => $table->string('phone_number')->nullable()->after('metadata'),
            'address' => fn (Blueprint $table) => $table->text('address')->nullable()->after('phone_number'),
            'city' => fn (Blueprint $table) => $table->string('city')->nullable()->after('address'),
            'country' => fn (Blueprint $table) => $table->string('country')->nullable()->after('city'),
            'postal_code' => fn (Blueprint $table) => $table->string('postal_code')->nullable()->after('country'),
            'job_title' => fn (Blueprint $table) => $table->string('job_title')->nullable()->after('postal_code'),
            'company' => fn (Blueprint $table) => $table->string('company')->nullable()->after('job_title'),
            'industry' => fn (Blueprint $table) => $table->string('industry')->nullable()->after('company'),
            'skills' => fn (Blueprint $table) => $table->text('skills')->nullable()->after('industry'),
            'experience_years' => fn (Blueprint $table) => $table->string('experience_years')->nullable()->after('skills'),
            'show_email' => fn (Blueprint $table) => $table->boolean('show_email')->default(false)->after('experience_years'),
            'show_phone' => fn (Blueprint $table) => $table->boolean('show_phone')->default(false)->after('show_email'),
            'show_birthdate' => fn (Blueprint $table) => $table->boolean('show_birthdate')->default(false)->after('show_phone'),
            'show_location' => fn (Blueprint $table) => $table->boolean('show_location')->default(false)->after('show_birthdate'),
            'allow_friend_requests' => fn (Blueprint $table) => $table->boolean('allow_friend_requests')->default(true)->after('show_location'),
            'allow_messages' => fn (Blueprint $table) => $table->boolean('allow_messages')->default(true)->after('allow_friend_requests'),
            'show_online_status' => fn (Blueprint $table) => $table->boolean('show_online_status')->default(true)->after('allow_messages'),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('user_profiles', $column)) {
                Schema::table('user_profiles', function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            }
        }
    }

    public function down(): void
    {
        // Repair migration: keep columns because the original migration owns this schema.
    }
};
