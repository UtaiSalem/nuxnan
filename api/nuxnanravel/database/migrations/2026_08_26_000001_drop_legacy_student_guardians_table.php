<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * G-S6
 * Read/write paths have been moved to `guardians` + `student_guardian_links` completely.
 * `up()` refuses to drop if invariants fail. Data is backed up as JSONL before drop
 * so `down()` can restore actual data.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_guardians')) {
            // Stage 1: Safety checks
            $legacyIds = DB::table('student_guardians')->pluck('id')->all();
            $referenced = DB::table('student_guardian_links')
                ->pluck('legacy_row_ids')
                ->flatMap(fn ($json) => json_decode($json ?: '[]', true) ?: [])
                ->unique()
                ->all();
            $missing = array_diff($legacyIds, $referenced);
            if ($missing !== []) {
                throw new RuntimeException(
                    'Refusing to drop student_guardians: '.count($missing).' legacy rows are not carried by any student_guardian_links row. '
                    .'Run `php -d memory_limit=2G -d xdebug.mode=off artisan guardians:backfill --force` first.'
                );
            }

            $orphanContacts = DB::table('guardian_contacts')->whereNull('guardian_person_id')->count();
            if ($orphanContacts > 0) {
                throw new RuntimeException(
                    'Refusing to drop guardian_contacts.guardian_id: '.$orphanContacts.' contact rows still have no guardian_person_id. '
                    .'Run `php -d memory_limit=2G -d xdebug.mode=off artisan guardians:backfill --force` first.'
                );
            }

            // Stage 2: Back the data up, and prove the file landed before anything is dropped.
            $this->dump('backups/student_guardians_legacy.jsonl', DB::table('student_guardians')->orderBy('id'));
            $this->dump('backups/guardian_contacts_legacy_guardian_id.jsonl', DB::table('guardian_contacts')->select('id', 'guardian_id')->orderBy('id'));
        }

        // Stage 3: Manage guardian_contacts
        if (Schema::hasColumn('guardian_contacts', 'guardian_id')) {
            // SQLite refuses to drop a column an index still points at, and the test suite runs on
            // SQLite. MySQL drops such an index by itself, so this has to be explicit and tolerate
            // the index already being gone.
            if (Schema::hasIndex('guardian_contacts', 'guardian_contacts_guardian_id_foreign')) {
                Schema::table('guardian_contacts', fn (Blueprint $table) => $table->dropIndex('guardian_contacts_guardian_id_foreign'));
            }

            Schema::table('guardian_contacts', fn (Blueprint $table) => $table->dropColumn('guardian_id'));
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `guardian_contacts` MODIFY `guardian_person_id` BIGINT UNSIGNED NOT NULL');
        }

        // Stage 4: Drop old table
        Schema::dropIfExists('student_guardians');
    }

    /**
     * Write every row of a query to a JSONL file, then read it back and count the lines.
     *
     * The first run of this migration on a real database produced no backup at all and dropped
     * the table anyway; the cause was never pinned down, and the `local` disk is configured with
     * `throw => false`, so a failed write returns false rather than raising. Hence: trust no
     * return value unverified. The line count has to match the row count or the migration stops
     * before any DDL runs.
     */
    private function dump(string $path, Builder $query): void
    {
        $disk = Storage::disk('local');
        $expected = (clone $query)->count();

        // An empty table has nothing to restore, and every RefreshDatabase test class runs this
        // migration against empty tables — writing here would truncate a real backup on every
        // `artisan test`. Leave whatever is on disk alone instead.
        if ($expected === 0) {
            return;
        }

        $disk->delete($path);

        (clone $query)->chunk(500, function ($rows) use ($disk, $path) {
            $lines = [];
            foreach ($rows as $row) {
                $lines[] = json_encode($row, JSON_UNESCAPED_UNICODE);
            }

            if ($disk->append($path, implode("\n", $lines), "\n") !== true) {
                throw new RuntimeException("Refusing to continue: writing the backup file [{$path}] failed.");
            }
        });

        $written = substr_count((string) $disk->get($path), "\n") + 1;

        if ($written !== $expected) {
            throw new RuntimeException(
                "Refusing to continue: backup [{$path}] holds {$written} lines but the table has {$expected} rows."
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert guardian_person_id to nullable
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `guardian_contacts` MODIFY `guardian_person_id` BIGINT UNSIGNED NULL');
        }

        // 2. Recreate student_guardians table
        if (! Schema::hasTable('student_guardians')) {
            Schema::create('student_guardians', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('academy_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->index('student_guardians_student_id_foreign');
                $table->string('student_code', 20)->nullable()->index()->comment('รหัสนักเรียน');
                $table->string('guardian_type', 50)->nullable()->index();
                $table->string('citizen_id', 13)->nullable()->comment('เลขประจำตัวประชาชน');
                $table->string('title_prefix', 20)->nullable()->comment('คำนำหน้าชื่อ');
                $table->string('first_name', 100)->comment('ชื่อ');
                $table->string('last_name', 100)->comment('นามสกุล');
                $table->string('occupation', 100)->nullable()->comment('อาชีพ');
                $table->string('workplace', 200)->nullable()->comment('สถานที่ทำงาน');
                $table->decimal('monthly_income', 10)->nullable()->comment('รายได้ต่อเดือน');
                $table->string('relationship', 50)->nullable()->comment('ความสัมพันธ์');
                $table->enum('status', ['alive', 'deceased', 'unknown'])->default('alive');
                $table->string('nationality', 50)->default('ไทย');
                $table->boolean('is_primary_contact')->default(false)->index();
                $table->boolean('is_emergency_contact')->default(false);
                $table->timestamps();
            });
        }

        // 3. Reload data for student_guardians
        if (Storage::disk('local')->exists('backups/student_guardians_legacy.jsonl')) {
            $handle = Storage::disk('local')->readStream('backups/student_guardians_legacy.jsonl');
            if ($handle) {
                $batch = [];
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $row = json_decode($line, true);
                    if ($row) {
                        $batch[] = $row;
                        if (count($batch) >= 500) {
                            DB::table('student_guardians')->insert($batch);
                            $batch = [];
                        }
                    }
                }
                if (count($batch) > 0) {
                    DB::table('student_guardians')->insert($batch);
                }
                fclose($handle);
            }
        } else {
            echo "Warning: backups/student_guardians_legacy.jsonl not found. Skipping data reload.\n";
        }

        // 4. Restore guardian_id in guardian_contacts
        if (! Schema::hasColumn('guardian_contacts', 'guardian_id')) {
            Schema::table('guardian_contacts', fn (Blueprint $table) => $table->unsignedBigInteger('guardian_id')->nullable()->index('guardian_contacts_guardian_id_foreign'));
        }

        if (Storage::disk('local')->exists('backups/guardian_contacts_legacy_guardian_id.jsonl')) {
            $handle = Storage::disk('local')->readStream('backups/guardian_contacts_legacy_guardian_id.jsonl');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $row = json_decode($line, true);
                    if ($row && isset($row['id'], $row['guardian_id'])) {
                        DB::table('guardian_contacts')
                            ->where('id', $row['id'])
                            ->update(['guardian_id' => $row['guardian_id']]);
                    }
                }
                fclose($handle);
            }
        } else {
            echo "Warning: backups/guardian_contacts_legacy_guardian_id.jsonl not found. Skipping data reload.\n";
        }

        if (DB::getDriverName() === 'mysql' && DB::table('guardian_contacts')->whereNull('guardian_id')->count() === 0) {
            DB::statement('ALTER TABLE `guardian_contacts` MODIFY `guardian_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};
