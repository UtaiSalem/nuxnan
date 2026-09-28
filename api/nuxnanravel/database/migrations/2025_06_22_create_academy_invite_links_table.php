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
        Schema::create('academy_invite_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the link');
            $table->string('code', 32)->unique()->comment('Unique invite code');
            $table->string('name', 100)->nullable()->comment('Name/label for the invite link');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('academy_role_id')->nullable()->comment('Default role for new members');
            $table->integer('max_uses')->nullable()->comment('Maximum number of uses, null = unlimited');
            $table->integer('use_count')->default(0)->comment('Current usage count');
            $table->timestamp('expires_at')->nullable()->comment('Expiration date, null = never expires');
            $table->boolean('is_active')->default(true);
            $table->boolean('require_approval')->default(true)->comment('Whether new members need approval');
            $table->json('allowed_domains')->nullable()->comment('Restrict to specific email domains');
            $table->json('metadata')->nullable()->comment('Additional settings');
            $table->timestamps();

            // FK academy_id → academies / academy_role_id → academy_roles ถูกย้ายไป repair migration
            // 2026_02_02_000000_repair_early_cross_table_foreign_keys เพราะตารางเหล่านั้นถูกสร้างทีหลัง
            // (academies=2025_10_26, academy_roles=2026_02_01) migrate จากศูนย์จึงตายที่ 1824 — ดู G25
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['academy_id', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_invite_links');
    }
};
