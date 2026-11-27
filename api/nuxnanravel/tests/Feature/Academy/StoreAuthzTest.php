<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\AcademyStore;
use App\Models\StoreCategory;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\StoreProduct;
use App\Models\StoreProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SA-2b — ร้านค้าโรงเรียน: เส้นจัดการร้านต้องใช้ store.manage + tenant ของ {product}/{category}/{order}/{review}
 */
class StoreAuthzTest extends TestCase
{
    use RefreshDatabase;

    /** 19 เส้นที่ต้องมี academy.permission:store.manage */
    private const MANAGE_ROUTES = [
        'api.academy.store.create',
        'api.academy.store.update',
        'api.academy.store.stats',
        'api.academy.store.logo',
        'api.academy.store.banner',
        'api.academy.store.categories.store',
        'api.academy.store.categories.update',
        'api.academy.store.categories.destroy',
        'api.academy.store.products.store',
        'api.academy.store.products.update',
        'api.academy.store.products.destroy',
        'api.academy.store.products.images.upload',
        'api.academy.store.products.images.delete',
        'api.academy.store.orders.index',
        'api.academy.store.orders.confirm',
        'api.academy.store.orders.process',
        'api.academy.store.orders.ready',
        'api.academy.store.orders.complete',
        'api.academy.store.reports.sales',
    ];

    /** @return array{0: Academy, 1: User, 2: AcademyStore} */
    private function academyWithStore(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $store = AcademyStore::create([
            'academy_id' => $academy->id,
            'name' => 'ร้านทดสอบ '.$academy->id,
        ]);

        return [$academy, $owner, $store];
    }

    /** สมาชิกที่อนุมัติแล้ว พร้อม permission ที่ระบุ (ว่าง = สมาชิกธรรมดา) */
    private function memberOf(Academy $academy, array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'บทบาททดสอบ',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return $user;
    }

    private function productIn(AcademyStore $store, array $attributes = []): StoreProduct
    {
        return StoreProduct::create(array_merge([
            'academy_store_id' => $store->id,
            'name' => 'สินค้า '.uniqid(),
            'price' => 50,
            'stock_quantity' => 10,
        ], $attributes));
    }

    private function categoryIn(AcademyStore $store): StoreCategory
    {
        return StoreCategory::create([
            'academy_store_id' => $store->id,
            'name' => 'หมวด '.uniqid(),
        ]);
    }

    private function orderIn(AcademyStore $store, User $buyer, string $status = StoreOrder::STATUS_PENDING): StoreOrder
    {
        return StoreOrder::create([
            'academy_store_id' => $store->id,
            'user_id' => $buyer->id,
            'status' => $status,
            'subtotal' => 50,
            'total' => 50,
            'payment_method' => 'wallet',
            // ไม่ให้ cancel ไปแตะการคืนเงิน (SchoolStoreService อยู่นอก shard นี้)
            'payment_status' => StoreOrder::PAYMENT_PENDING,
        ]);
    }

    private function reviewOf(StoreProduct $product, User $author): StoreProductReview
    {
        return StoreProductReview::create([
            'store_product_id' => $product->id,
            'user_id' => $author->id,
            'rating' => 4,
            'comment' => 'ดี',
        ]);
    }

    private function base(Academy $academy): string
    {
        return "/api/academies/{$academy->id}/store";
    }

    // 1. สมาชิกธรรมดาเรียกเส้นจัดการร้าน → 403
    public function test_plain_member_is_forbidden_from_store_management_routes(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $member = $this->memberOf($academy);
        $product = $this->productIn($store, ['price' => 50]);
        $order = $this->orderIn($store, $member, StoreOrder::STATUS_READY);
        $base = $this->base($academy);

        $this->actingAs($member, 'api');

        $this->postJson("{$base}/products", ['name' => 'แอบเพิ่ม', 'price' => 1, 'stock_quantity' => 1])->assertForbidden();
        $this->patchJson("{$base}/products/{$product->id}", ['price' => 1])->assertForbidden();
        $this->postJson("{$base}/orders/{$order->id}/complete")->assertForbidden();
        $this->getJson("{$base}/orders")->assertForbidden();
        $this->getJson("{$base}/reports/sales")->assertForbidden();

        $this->assertSame(1, StoreProduct::count());
        $this->assertEquals(50, $product->fresh()->price);
        $this->assertSame(StoreOrder::STATUS_READY, $order->fresh()->status);
    }

    // 2. ผู้มี store.manage และเจ้าของโรงเรียน → ผ่าน
    public function test_store_manage_holder_and_owner_can_use_store_management_routes(): void
    {
        [$academy, $owner, $store] = $this->academyWithStore();
        $manager = $this->memberOf($academy, ['store.manage']);
        $buyer = $this->memberOf($academy);
        $product = $this->productIn($store);
        $order = $this->orderIn($store, $buyer, StoreOrder::STATUS_READY);
        $base = $this->base($academy);

        $this->actingAs($manager, 'api');
        $this->postJson("{$base}/products", ['name' => 'สมุด', 'price' => 20, 'stock_quantity' => 5])->assertCreated();
        $this->patchJson("{$base}/products/{$product->id}", ['price' => 99])->assertOk();
        $this->postJson("{$base}/orders/{$order->id}/complete")->assertOk();
        $this->getJson("{$base}/orders")->assertOk();
        $this->getJson("{$base}/reports/sales")->assertOk();

        $this->assertEquals(99, $product->fresh()->price);
        $this->assertSame(StoreOrder::STATUS_COMPLETED, $order->fresh()->status);

        $this->actingAs($owner, 'api');
        $this->postJson("{$base}/products", ['name' => 'ดินสอ', 'price' => 5, 'stock_quantity' => 5])->assertCreated();
        $this->getJson("{$base}/orders")->assertOk();
        $this->getJson("{$base}/reports/sales")->assertOk();
    }

    // 3. รายการของโรงเรียน B ยิงผ่าน URL โรงเรียน A (มี store.manage ของ A) → 404
    public function test_records_of_another_school_are_not_reachable_through_this_school_url(): void
    {
        [$academyA, , $storeA] = $this->academyWithStore();
        [, , $storeB] = $this->academyWithStore();
        $managerA = $this->memberOf($academyA, ['store.manage']);
        $buyerB = User::factory()->create();

        $productB = $this->productIn($storeB, ['price' => 70]);
        $categoryB = $this->categoryIn($storeB);
        $orderB = $this->orderIn($storeB, $buyerB);
        $reviewB = $this->reviewOf($productB, $buyerB);
        $this->productIn($storeA);
        $base = $this->base($academyA);

        $this->actingAs($managerA, 'api');

        $this->getJson("{$base}/products/{$productB->id}")->assertNotFound();
        $this->patchJson("{$base}/products/{$productB->id}", ['price' => 1])->assertNotFound();
        $this->deleteJson("{$base}/products/{$productB->id}")->assertNotFound();
        $this->deleteJson("{$base}/products/{$productB->id}/images", ['image_index' => 0])->assertNotFound();
        $this->patchJson("{$base}/categories/{$categoryB->id}", ['name' => 'แฮก'])->assertNotFound();
        $this->deleteJson("{$base}/categories/{$categoryB->id}")->assertNotFound();
        $this->getJson("{$base}/orders/{$orderB->id}")->assertNotFound();
        $this->postJson("{$base}/orders/{$orderB->id}/confirm")->assertNotFound();
        $this->postJson("{$base}/orders/{$orderB->id}/cancel", ['reason' => 'แฮก'])->assertNotFound();
        $this->getJson("{$base}/products/{$productB->id}/reviews")->assertNotFound();
        $this->deleteJson("{$base}/products/{$productB->id}/reviews/{$reviewB->id}")->assertNotFound();

        $this->assertEquals(70, $productB->fresh()->price);
        $this->assertNotNull(StoreProduct::find($productB->id));
        $this->assertNotNull($categoryB->fresh());
        $this->assertSame(StoreOrder::STATUS_PENDING, $orderB->fresh()->status);
        $this->assertNotNull($reviewB->fresh());
    }

    // 4. ผู้ซื้อดู/ยกเลิกออเดอร์ของตัวเองได้ · ของคนอื่น → 403
    public function test_buyer_can_view_and_cancel_only_their_own_order(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $buyer = $this->memberOf($academy);
        $other = $this->memberOf($academy);
        $manager = $this->memberOf($academy, ['store.manage']);
        $mine = $this->orderIn($store, $buyer);
        $theirs = $this->orderIn($store, $other);
        $base = $this->base($academy);

        $this->actingAs($buyer, 'api');
        $this->getJson("{$base}/orders/{$mine->id}")->assertOk()->assertJsonPath('data.id', $mine->id);
        $this->getJson("{$base}/orders/{$theirs->id}")->assertForbidden();
        $this->postJson("{$base}/orders/{$theirs->id}/cancel", ['reason' => 'ไม่ใช่ของฉัน'])->assertForbidden();
        $this->assertSame(StoreOrder::STATUS_PENDING, $theirs->fresh()->status);

        $this->postJson("{$base}/orders/{$mine->id}/cancel", ['reason' => 'เปลี่ยนใจ'])->assertOk();
        $this->assertSame(StoreOrder::STATUS_CANCELLED, $mine->fresh()->status);

        // ผู้มี store.manage ดูออเดอร์ของคนอื่นได้
        $this->actingAs($manager, 'api');
        $this->getJson("{$base}/orders/{$theirs->id}")->assertOk();
    }

    // 5. ลบรีวิวของคนอื่น → 403 · ลบของตัวเอง → ผ่าน
    public function test_member_can_delete_only_their_own_review(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $author = $this->memberOf($academy);
        $other = $this->memberOf($academy);
        $manager = $this->memberOf($academy, ['store.manage']);
        $product = $this->productIn($store);
        $otherProduct = $this->productIn($store);
        $mine = $this->reviewOf($product, $author);
        $theirs = $this->reviewOf($product, $other);
        $elsewhere = $this->reviewOf($otherProduct, $author);
        $base = $this->base($academy);

        $this->actingAs($author, 'api');
        $this->deleteJson("{$base}/products/{$product->id}/reviews/{$theirs->id}")->assertForbidden();
        $this->assertNotNull($theirs->fresh());

        // รีวิวของสินค้าอื่นยิงผ่าน URL ของสินค้านี้ → 404
        $this->deleteJson("{$base}/products/{$product->id}/reviews/{$elsewhere->id}")->assertNotFound();
        $this->assertNotNull($elsewhere->fresh());

        $this->deleteJson("{$base}/products/{$product->id}/reviews/{$mine->id}")->assertOk();
        $this->assertNull($mine->fresh());

        // ผู้มี store.manage ลบรีวิวของคนอื่นได้
        $this->actingAs($manager, 'api');
        $this->deleteJson("{$base}/products/{$product->id}/reviews/{$theirs->id}")->assertOk();
        $this->assertNull($theirs->fresh());
    }

    // 6. category_id ของร้านอื่นตอนสร้าง/แก้สินค้า → 422
    public function test_category_from_another_store_is_rejected_on_product_create_and_update(): void
    {
        [$academyA, , $storeA] = $this->academyWithStore();
        [, , $storeB] = $this->academyWithStore();
        $managerA = $this->memberOf($academyA, ['store.manage']);
        $categoryA = $this->categoryIn($storeA);
        $categoryB = $this->categoryIn($storeB);
        $product = $this->productIn($storeA);
        $base = $this->base($academyA);

        $this->actingAs($managerA, 'api');

        $this->postJson("{$base}/products", [
            'name' => 'ข้ามร้าน',
            'price' => 10,
            'stock_quantity' => 1,
            'category_id' => $categoryB->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');

        $this->patchJson("{$base}/products/{$product->id}", ['category_id' => $categoryB->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
        $this->assertNull($product->fresh()->category_id);

        $this->postJson("{$base}/products", [
            'name' => 'ในร้าน',
            'price' => 10,
            'stock_quantity' => 1,
            'category_id' => $categoryA->id,
        ])->assertCreated();
    }

    // 7. เส้นผู้ซื้อยังใช้ได้สำหรับสมาชิกธรรมดา
    public function test_buyer_routes_still_work_for_plain_member(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $member = $this->memberOf($academy);
        $product = $this->productIn($store);
        $this->orderIn($store, $member);
        $base = $this->base($academy);

        $this->actingAs($member, 'api');
        $this->getJson("{$base}/products")->assertOk()->assertJsonPath('data.0.id', $product->id);
        $this->getJson("{$base}/products/{$product->id}")->assertOk();
        $this->getJson("{$base}/orders/my")->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson("{$base}/categories")->assertOk();
        $this->getJson("{$base}/products/{$product->id}/reviews")->assertOk();
    }

    public function test_review_rejects_another_users_order_in_the_same_store(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $reviewer = $this->memberOf($academy);
        $other = $this->memberOf($academy);
        $product = $this->productIn($store);
        $order = $this->orderIn($store, $other);
        StoreOrderItem::create([
            'store_order_id' => $order->id,
            'store_product_id' => $product->id,
            'product_name' => $product->name,
        ]);
        $base = $this->base($academy);

        $this->actingAs($reviewer, 'api');
        $this->postJson("{$base}/products/{$product->id}/reviews", [
            'rating' => 5,
            'order_id' => $order->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('store_product_reviews', 0);
    }

    public function test_review_rejects_own_order_from_another_schools_store(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        [, , $otherStore] = $this->academyWithStore();
        $reviewer = $this->memberOf($academy);
        $product = $this->productIn($store);
        $order = $this->orderIn($otherStore, $reviewer);
        $base = $this->base($academy);

        $this->actingAs($reviewer, 'api');
        $this->postJson("{$base}/products/{$product->id}/reviews", [
            'rating' => 5,
            'order_id' => $order->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('store_product_reviews', 0);
    }

    public function test_review_rejects_own_order_that_does_not_contain_the_product(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $reviewer = $this->memberOf($academy);
        $product = $this->productIn($store);
        $otherProduct = $this->productIn($store);
        $order = $this->orderIn($store, $reviewer);
        StoreOrderItem::create([
            'store_order_id' => $order->id,
            'store_product_id' => $otherProduct->id,
            'product_name' => $otherProduct->name,
        ]);
        $base = $this->base($academy);

        $this->actingAs($reviewer, 'api');
        $this->postJson("{$base}/products/{$product->id}/reviews", [
            'rating' => 5,
            'order_id' => $order->id,
        ])->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'คำสั่งซื้อนี้ไม่มีสินค้านี้',
        ]);

        $this->assertDatabaseCount('store_product_reviews', 0);
    }

    public function test_review_accepts_own_order_containing_the_product_and_saves_order_id(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $reviewer = $this->memberOf($academy);
        $product = $this->productIn($store);
        $order = $this->orderIn($store, $reviewer);
        StoreOrderItem::create([
            'store_order_id' => $order->id,
            'store_product_id' => $product->id,
            'product_name' => $product->name,
        ]);
        $base = $this->base($academy);

        $this->actingAs($reviewer, 'api');
        $response = $this->postJson("{$base}/products/{$product->id}/reviews", [
            'rating' => 5,
            'order_id' => $order->id,
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('store_product_reviews', [
            'id' => $response->json('data.id'),
            'store_product_id' => $product->id,
            'user_id' => $reviewer->id,
            'store_order_id' => $order->id,
        ]);
    }

    public function test_review_without_order_id_still_works(): void
    {
        [$academy, , $store] = $this->academyWithStore();
        $reviewer = $this->memberOf($academy);
        $product = $this->productIn($store);
        $base = $this->base($academy);

        $this->actingAs($reviewer, 'api');
        $response = $this->postJson("{$base}/products/{$product->id}/reviews", [
            'rating' => 5,
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('store_product_reviews', [
            'id' => $response->json('data.id'),
            'store_product_id' => $product->id,
            'user_id' => $reviewer->id,
            'store_order_id' => null,
        ]);
    }

    // 8. ครบ 19 เส้นที่มี store.manage และเส้นผู้ซื้อไม่มี
    public function test_store_manage_middleware_is_on_exactly_the_management_routes(): void
    {
        $guarded = [];
        $storeRouteCount = 0;

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/academies/{academy}/store')) {
                continue;
            }
            $storeRouteCount++;

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:api', $middleware, $route->uri());

            if (in_array('academy.permission:store.manage', $middleware, true)) {
                $guarded[] = $route->getName();
            }
        }

        sort($guarded);
        $expected = self::MANAGE_ROUTES;
        sort($expected);

        $this->assertSame(30, $storeRouteCount);
        $this->assertCount(19, $guarded);
        $this->assertSame($expected, $guarded);
    }
}
