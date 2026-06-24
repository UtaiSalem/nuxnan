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
        // ก่อน drop ต้องปรับ collation ให้ตรงกับ users.phone_number ก่อน
        // เผื่อมี data ตกหล่นที่ต้องย้ายไป users ก่อน drop (ปัจจุบันตรวจแล้วเป็น NULL ทั้งหมด)
        \DB::table('user_profiles')
            ->join('users', 'user_profiles.user_id', '=', 'users.id')
            ->whereNotNull('user_profiles.phone_number')
            ->whereNull('users.phone_number')
            ->update([
                'users.phone_number' => \DB::raw('user_profiles.phone_number')
            ]);

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('metadata');
        });
    }
};
