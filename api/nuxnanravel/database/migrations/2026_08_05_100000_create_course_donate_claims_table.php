<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_donate_claims')) {
            Schema::create('course_donate_claims', function (Blueprint $table) {
                $table->id();
                // The legacy course_donates table is not FK-compatible in some deployed schemas.
                $table->unsignedBigInteger('course_donate_id');
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('claimer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('suggester_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('amount_claimer')->default(210);
                $table->unsignedInteger('amount_suggester')->default(30);
                $table->unsignedInteger('amount_course')->default(20);
                $table->unsignedInteger('amount_platform')->default(10);
                $table->foreignId('claimer_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
                $table->foreignId('suggester_transaction_id')->nullable()->constrained('points_transactions')->nullOnDelete();
                $table->foreignId('course_transaction_id')->constrained('course_point_transactions')->cascadeOnDelete();
                $table->foreignId('platform_transaction_id')->constrained('points_transactions')->cascadeOnDelete();
                $table->timestamp('claimed_at');
                $table->timestamps();
                // Explicit names: the auto-generated ones exceed MySQL's 64-char identifier limit.
                $table->index(['course_donate_id', 'claimer_id', 'claimed_at'], 'cdc_donate_claimer_at_idx');
                $table->index(['claimer_id', 'claimed_at'], 'cdc_claimer_at_idx');
            });
        }

        // SQL SECURITY INVOKER keeps the view working after a dump is restored on a
        // host where the original definer does not exist. MySQL-only syntax.
        $security = DB::getDriverName() === 'mysql' ? 'SQL SECURITY INVOKER ' : '';

        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
        DB::statement("CREATE {$security}VIEW user_daily_claim_counters AS SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients UNION ALL SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims UNION ALL SELECT claimer_id AS user_id, claimed_at, 'course' AS tier FROM course_donate_claims");

        // Deliberately no data step here. Legacy course donations carry a stale
        // remaining_points (the old campaign flow never decremented it) while their
        // points were already credited to the fund and partly handed out. Zeroing
        // them silently would strand real donors' points, so each environment must
        // be reconciled on purpose instead — refund the donor, reverse any claims,
        // then clear the rows. See scratchpad/reverse-course-donations.php for the
        // script used on the development database on 2026-08-05.
    }

    public function down(): void
    {
        Schema::dropIfExists('course_donate_claims');
        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
        DB::statement("CREATE VIEW user_daily_claim_counters AS SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients UNION ALL SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims");
    }
};
