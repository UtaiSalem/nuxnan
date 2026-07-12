<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->string('campaign_type')->default('advertisement')->index();
            $table->string('scope_type')->default('public')->index();
            $table->foreignId('academy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('inherit_to_academy')->default(false);
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('review_status')->default('pending')->index();
            $table->decimal('budget_amount', 10, 2)->nullable();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->index(['scope_type', 'academy_id', 'review_status']);
            $table->index(['scope_type', 'course_id', 'review_status']);
            $table->index(['campaign_type', 'review_status', 'remaining_views']);
            $table->unique(['user_id', 'idempotency_key']);
        });

        Schema::table('advert_viewers', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('user_id');
            $table->index(['advert_id', 'user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('advert_viewers', function (Blueprint $table) {
            $table->dropIndex(['advert_id', 'user_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('adverts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropIndex(['scope_type', 'academy_id', 'review_status']);
            $table->dropIndex(['scope_type', 'course_id', 'review_status']);
            $table->dropIndex(['campaign_type', 'review_status', 'remaining_views']);
            $table->dropConstrainedForeignId('academy_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('beneficiary_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'campaign_type', 'scope_type', 'inherit_to_academy', 'payment_status',
                'review_status', 'budget_amount', 'active_from', 'active_until',
                'idempotency_key', 'impressions_count', 'reviewed_at', 'refunded_at', 'distributed_at',
            ]);
        });
    }
};
