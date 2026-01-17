# Coupon System Architecture

## Overview
This document describes the architecture for a coupon system that allows users to create coupons from their accumulated points or wallet balance. Users can generate printable coupons with QR codes and redemption codes, which can be scanned or entered to add points or money to their wallets.

## System Requirements

### User Features
1. Create coupons from accumulated points
2. Create coupons from wallet balance
3. Generate printable coupons with QR codes
4. Generate unique redemption codes
5. Scan QR codes to redeem coupons
6. Enter redemption codes manually
7. View coupon history and status

### Admin Features
1. View all coupons
2. Monitor coupon redemptions
3. Manage coupon settings

## Database Schema

### 1. Coupons Table
Stores coupon information including type, amount, status, and redemption details.

```sql
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    coupon_code VARCHAR(32) UNIQUE NOT NULL,
    coupon_type ENUM('points', 'wallet') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('active', 'redeemed', 'expired', 'cancelled') DEFAULT 'active',
    description TEXT,
    qr_code_path VARCHAR(255),
    expires_at TIMESTAMP NULL,
    redeemed_at TIMESTAMP NULL,
    redeemed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_coupon_code (coupon_code),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_coupon_type (coupon_type)
);
```

### 2. Coupon Redemptions Table
Tracks redemption history for auditing and analytics.

```sql
CREATE TABLE coupon_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_coupon_id (coupon_id),
    INDEX idx_user_id (user_id),
    INDEX idx_redeemed_at (redeemed_at)
);
```

## Backend Architecture

### Models

#### Coupon Model
```php
namespace App\Models;

class Coupon extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_code',
        'coupon_type',
        'amount',
        'status',
        'description',
        'qr_code_path',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function redeemedBy() { return $this->belongsTo(User::class, 'redeemed_by'); }
    public function redemptions() { return $this->hasMany(CouponRedemption::class); }

    // Scopes
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopePoints($query) { return $query->where('coupon_type', 'points'); }
    public function scopeWallet($query) { return $query->where('coupon_type', 'wallet'); }
}
```

#### CouponRedemption Model
```php
namespace App\Models;

class CouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'redeemed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    // Relationships
    public function coupon() { return $this->belongsTo(Coupon::class); }
    public function user() { return $this->belongsTo(User::class); }
}
```

### Services

#### CouponService
Handles business logic for coupon operations.

```php
namespace App\Services;

class CouponService
{
    // Generate unique coupon code
    public function generateCouponCode(): string

    // Create coupon from points
    public function createPointsCoupon(User $user, float $points, ?string $description = null): Coupon

    // Create coupon from wallet
    public function createWalletCoupon(User $user, float $amount, ?string $description = null): Coupon

    // Redeem coupon by code
    public function redeemCoupon(User $user, string $couponCode): array

    // Redeem coupon by ID
    public function redeemCouponById(User $user, int $couponId): array

    // Generate QR code for coupon
    public function generateQRCode(Coupon $coupon): string

    // Cancel coupon
    public function cancelCoupon(Coupon $coupon): bool

    // Get user coupons
    public function getUserCoupons(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
}
```

### Controllers

#### CouponController
```php
namespace App\Http\Controllers\Api;

class CouponController extends Controller
{
    // POST /api/coupons - Create coupon
    public function create(Request $request): JsonResponse

    // GET /api/coupons - List user's coupons
    public function index(Request $request): JsonResponse

    // GET /api/coupons/{id} - Get coupon details
    public function show(int $id): JsonResponse

    // POST /api/coupons/redeem - Redeem coupon by code
    public function redeem(Request $request): JsonResponse

    // POST /api/coupons/{id}/cancel - Cancel coupon
    public function cancel(int $id): JsonResponse

    // GET /api/coupons/{id}/print - Get printable coupon
    public function print(int $id): JsonResponse
}
```

### API Routes
```php
// api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('coupons')->group(function () {
        Route::post('/', [CouponController::class, 'create']);
        Route::get('/', [CouponController::class, 'index']);
        Route::get('/{id}', [CouponController::class, 'show']);
        Route::post('/redeem', [CouponController::class, 'redeem']);
        Route::post('/{id}/cancel', [CouponController::class, 'cancel']);
        Route::get('/{id}/print', [CouponController::class, 'print']);
    });
});
```

## QR Code Generation

### Library: `simplesoftwareio/simple-qrcode`

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

public function generateQRCode(Coupon $coupon): string
{
    $qrData = json_encode([
        'coupon_code' => $coupon->coupon_code,
        'type' => $coupon->coupon_type,
        'amount' => $coupon->amount,
    ]);

    $fileName = 'qr-codes/' . $coupon->coupon_code . '.png';
    $path = storage_path('app/public/' . $fileName);

    QrCode::format('png')
        ->size(300)
        ->margin(2)
        ->generate($qrData, $path);

    return $fileName;
}
```

## Frontend Architecture

### Components

#### CouponCreationForm.vue
Form for creating new coupons from points or wallet.

```vue
<template>
  <div class="coupon-creation-form">
    <h2>Create Coupon</h2>
    
    <div class="coupon-type-selector">
      <button @click="setType('points')" :class="{ active: type === 'points' }">
        Points Coupon
      </button>
      <button @click="setType('wallet')" :class="{ active: type === 'wallet' }">
        Wallet Coupon
      </button>
    </div>

    <form @submit.prevent="createCoupon">
      <div class="form-group">
        <label>Amount</label>
        <input v-model.number="amount" type="number" min="1" required />
        <span class="unit">{{ type === 'points' ? 'points' : 'THB' }}</span>
      </div>

      <div class="form-group">
        <label>Description (Optional)</label>
        <textarea v-model="description" rows="3"></textarea>
      </div>

      <div class="balance-info">
        <p>Available {{ type }}: {{ availableBalance }}</p>
      </div>

      <button type="submit" :disabled="!isValid">
        Create Coupon
      </button>
    </form>
  </div>
</template>
```

#### CouponCard.vue
Display coupon information with QR code.

```vue
<template>
  <div class="coupon-card" :class="statusClass">
    <div class="coupon-header">
      <h3>{{ couponTypeLabel }}</h3>
      <span class="status">{{ statusLabel }}</span>
    </div>

    <div class="coupon-body">
      <div class="amount">
        <span class="value">{{ formatAmount }}</span>
        <span class="unit">{{ coupon.coupon_type === 'points' ? 'points' : 'THB' }}</span>
      </div>

      <div class="qr-code" v-if="coupon.qr_code_path">
        <img :src="qrCodeUrl" alt="QR Code" />
      </div>

      <div class="coupon-code">
        <label>Redemption Code:</label>
        <code>{{ coupon.coupon_code }}</code>
        <button @click="copyCode">Copy</button>
      </div>
    </div>

    <div class="coupon-footer">
      <button @click="printCoupon" class="print-btn">Print</button>
      <button @click="cancelCoupon" class="cancel-btn" v-if="canCancel">Cancel</button>
    </div>
  </div>
</template>
```

#### CouponRedemption.vue
Component for redeeming coupons via QR scan or code entry.

```vue
<template>
  <div class="coupon-redemption">
    <h2>Redeem Coupon</h2>

    <div class="redemption-methods">
      <div class="method-selector">
        <button @click="method = 'scan'" :class="{ active: method === 'scan' }">
          Scan QR Code
        </button>
        <button @click="method = 'code'" :class="{ active: method === 'code' }">
          Enter Code
        </button>
      </div>

      <div class="scan-section" v-if="method === 'scan'">
        <div class="scanner-container">
          <video ref="scanner" autoplay></video>
          <canvas ref="canvas" style="display:none;"></canvas>
        </div>
        <button @click="startScanning">Start Scanner</button>
        <button @click="stopScanning">Stop Scanner</button>
      </div>

      <div class="code-section" v-if="method === 'code'">
        <input 
          v-model="couponCode" 
          placeholder="Enter coupon code" 
          @keyup.enter="redeemCoupon"
        />
        <button @click="redeemCoupon" :disabled="!couponCode">
          Redeem
        </button>
      </div>
    </div>

    <div class="result" v-if="result">
      <div :class="['message', result.success ? 'success' : 'error']">
        {{ result.message }}
      </div>
      <div class="details" v-if="result.success">
        <p>{{ result.type === 'points' ? 'Points' : 'THB' }} added: {{ result.amount }}</p>
        <p>New balance: {{ result.newBalance }}</p>
      </div>
    </div>
  </div>
</template>
```

#### CouponList.vue
List all user coupons with filtering.

```vue
<template>
  <div class="coupon-list">
    <div class="filters">
      <select v-model="filterType">
        <option value="all">All Types</option>
        <option value="points">Points</option>
        <option value="wallet">Wallet</option>
      </select>
      <select v-model="filterStatus">
        <option value="all">All Status</option>
        <option value="active">Active</option>
        <option value="redeemed">Redeemed</option>
        <option value="expired">Expired</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="coupons">
      <CouponCard 
        v-for="coupon in filteredCoupons" 
        :key="coupon.id"
        :coupon="coupon"
        @redeemed="loadCoupons"
      />
    </div>

    <div class="empty-state" v-if="filteredCoupons.length === 0">
      <p>No coupons found</p>
    </div>
  </div>
</template>
```

### Pages

#### coupons/index.vue
Main coupons page with creation and listing.

```vue
<template>
  <div class="coupons-page">
    <div class="page-header">
      <h1>My Coupons</h1>
      <button @click="showCreateModal = true" class="create-btn">
        + Create Coupon
      </button>
    </div>

    <CouponList :coupons="coupons" />

    <Modal v-if="showCreateModal" @close="showCreateModal = false">
      <CouponCreationForm 
        @created="handleCouponCreated"
        @cancel="showCreateModal = false"
      />
    </Modal>
  </div>
</template>
```

#### coupons/redeem.vue
Coupon redemption page.

```vue
<template>
  <div class="redeem-page">
    <h1>Redeem Coupon</h1>
    <CouponRedemption />
  </div>
</template>
```

## Security Considerations

1. **Coupon Code Generation**: Use cryptographically secure random strings
2. **QR Code Security**: Validate QR code data structure before processing
3. **Rate Limiting**: Implement rate limiting on redemption endpoint
4. **Authentication**: All coupon operations require authentication
5. **Authorization**: Users can only cancel their own active coupons
6. **Expiration**: Support optional expiration dates for coupons
7. **Single Use**: Each coupon can only be redeemed once
8. **Audit Trail**: Track all redemption attempts with IP and user agent

## Integration with Existing Systems

### Points System
- Creating a points coupon deducts points from user's balance
- Redeeming a points coupon adds points to user's balance
- Uses existing `PointsService` for point transactions

### Wallet System
- Creating a wallet coupon deducts money from user's wallet
- Redeeming a wallet coupon adds money to user's wallet
- Uses existing `WalletService` for wallet transactions

## Testing Strategy

### Unit Tests
- CouponService methods
- Coupon model scopes and relationships
- QR code generation
- Coupon code uniqueness

### Integration Tests
- Coupon creation API endpoints
- Coupon redemption API endpoints
- Integration with PointsService
- Integration with WalletService

### End-to-End Tests
- Complete coupon creation flow
- Complete coupon redemption flow
- QR code scanning and redemption
- Error handling scenarios

## Deployment Checklist

1. Install QR code library: `composer require simplesoftwareio/simple-qrcode`
2. Run database migrations
3. Add API routes
4. Deploy frontend components
5. Configure storage for QR codes
6. Set up rate limiting
7. Configure coupon expiration cleanup job
8. Update API documentation

## Future Enhancements

1. Bulk coupon creation
2. Coupon templates
3. Coupon sharing between users
4. Coupon analytics dashboard
5. Email/SMS coupon delivery
6. Coupon expiration notifications
7. Admin coupon management interface
8. Coupon usage statistics and reporting
