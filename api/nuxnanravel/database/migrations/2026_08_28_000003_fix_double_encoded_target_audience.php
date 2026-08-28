<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['school_announcements', 'school_events'];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->whereNotNull('target_audience')->get();

            $updated = 0;
            foreach ($rows as $row) {
                $value = $row->target_audience;
                $decoded = json_decode($value, true);

                if (is_string($decoded)) {
                    $decodedTwice = json_decode($decoded, true);
                    if (is_array($decodedTwice)) {
                        DB::table($table)->where('id', $row->id)->update([
                            'target_audience' => json_encode($decodedTwice),
                        ]);
                        $updated++;
                    }
                }
            }

            if ($updated > 0) {
                echo "Fixed {$updated} double-encoded rows in {$table}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // จงใจไม่ย้อน: การย้อนคือการเขียนข้อมูลผิดรูปกลับเข้าไป ซึ่งไม่ใช่การกู้คืนที่มีประโยชน์
        // และไม่มีทางรู้ว่าแถวไหนเดิมเสียบ้างหลังจากแก้ไปแล้ว
    }
};
