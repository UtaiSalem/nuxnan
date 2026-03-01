<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Academy Stores - ร้านค้าของโรงเรียน
        Schema::create('academy_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_points_payment')->default(true);
            $table->boolean('allow_wallet_payment')->default(true);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('academy_id'); // One store per academy
        });

        // 2. Store Categories - หมวดหมู่สินค้า
        Schema::create('store_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_store_id')->constrained('academy_stores')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academy_store_id', 'slug']);
        });

        // 3. Store Products - สินค้า
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_store_id')->constrained('academy_stores')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->enum('payment_type', ['points', 'wallet', 'both'])->default('both');
            $table->integer('points_price')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('total_sold')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academy_store_id', 'slug']);
            $table->index(['academy_store_id', 'is_active', 'is_featured']);
            $table->index(['category_id', 'is_active']);
        });

        // 4. Store Orders - คำสั่งซื้อ
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_store_id')->constrained('academy_stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'ready',
                'completed',
                'cancelled',
                'refunded'
            ])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_method', ['points', 'wallet'])->default('wallet');
            $table->integer('points_spent')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['academy_store_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('order_number');
        });

        // 5. Store Order Items - สินค้าในคำสั่งซื้อ
        Schema::create('store_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignId('store_product_id')->nullable()->constrained('store_products')->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('unit_points_price')->nullable();
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 6. Store Product Reviews - รีวิวสินค้า
        Schema::create('store_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_product_id')->constrained('store_products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_order_id')->nullable()->constrained('store_orders')->nullOnDelete();
            $table->tinyInteger('rating')->unsigned()->default(5);
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->unique(['store_product_id', 'user_id', 'store_order_id'], 'spr_product_user_order_unique');
            $table->index(['store_product_id', 'is_approved'], 'spr_product_approved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_product_reviews');
        Schema::dropIfExists('store_order_items');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('store_categories');
        Schema::dropIfExists('academy_stores');
    }
};
