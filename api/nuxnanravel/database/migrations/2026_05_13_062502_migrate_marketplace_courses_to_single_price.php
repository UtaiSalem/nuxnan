<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // แปลงราคาแต้มเป็น THB สำหรับ courses เดิม
        DB::table('courses')
            ->where('price_type', 'points')
            ->where('price_points', '>', 0)
            ->update([
                'price' => DB::raw('CEIL(price_points / 1200)'),
            ]);

        // courses ฟรียังคง price=0
        DB::table('courses')
            ->where('price_type', 'free')
            ->update(['price' => 0]);
    }

    public function down(): void
    {
        // ไม่ rollback (lossy migration)
    }
};
