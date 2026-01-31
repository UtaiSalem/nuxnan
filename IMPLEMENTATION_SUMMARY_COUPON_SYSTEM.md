# Coupon System Implementation Summary

## Overview
This document summarizes the complete implementation of a coupon system that allows users to create coupons from their accumulated points or wallet balance. Users can generate printable coupons with QR codes and redemption codes, which can be scanned or entered to add points or money to their wallets.

## Implementation Date
January 16, 2026

## System Features

### User Features
1. **Create Coupons from Points**
   - Users can create coupons using their accumulated points
   - Points are deducted immediately upon coupon creation
   - Support for optional expiration dates
   - Custom descriptions for each coupon

2. **Create Coupons from Wallet**
   - Users can create coupons using their wallet balance
   - Money is deducted immediately upon coupon creation
   - Support for optional expiration dates
   - Custom descriptions for each coupon

3. **QR Code Generation**
   - Automatic QR code generation for each coupon
   - QR codes contain coupon type, amount, and redemption code
   - QR codes are stored and accessible via URL

4. **Coupon Management**
   - View all created coupons
   - Filter by type (points/wallet) and status (active/redeemed/expired/cancelled)
   - View coupon statistics
   - Cancel active coupons (refunds points/money)
   - Print coupons with QR codes

5. **Coupon Redemption**
   - Scan QR codes using device camera
   - Enter redemption codes manually
   - Paste codes from clipboard
   - View redemption results with new balances
   - Single-use redemption (each coupon can only be redeemed once)

## Technical Implementation

### Backend (Laravel)

#### Database Models

1. **Coupon Model** ([`Coupon.php`](api/nuxnanravel/app/Models/Coupon.php))
   - Stores coupon information
   - Fields: user_id, coupon_code, coupon_type, amount, status, description, qr_code_path, expires_at, redeemed_at, redeemed_by
   - Status: active, redeemed, expired, cancelled
   - Type: points, wallet
   - Relationships: user, redeemedBy, redemptions
   - Scopes: active, points, wallet, notExpired
   - Accessors: status_label, type_label, is_expired, can_redeem, qr_code_url, formatted_amount

2. **CouponRedemption Model** ([`CouponRedemption.php`](api/nuxnanravel/app/Models/CouponRedemption.php))
   - Tracks redemption history for auditing
   - Fields: coupon_id, user_id, redeemed_at, ip_address, user_agent
   - Relationships: coupon, user
   - Scopes: dateRange, forUser, forCoupon

#### Database Migrations

1. **Coupons Table** ([`2026_01_16_000001_create_coupons_table.php`](api/nuxnanravel/database/migrations/2026_01_16_000001_create_coupons_table.php))
   - Primary key: id
   - Foreign keys: user_id, redeemed_by
   - Indexes: coupon_code, user_id, status, coupon_type, expires_at
   - Status enum: active, redeemed, expired, cancelled
   - Type enum: points, wallet

2. **Coupon Redemptions Table** ([`2026_01_16_000002_create_coupon_redemptions_table.php`](api/nuxnanravel/database/migrations/2026_01_16_000002_create_coupon_redemptions_table.php))
   - Primary key: id
   - Foreign keys: coupon_id, user_id
   - Indexes: coupon_id, user_id, redeemed_at

#### Services

**CouponService** ([`CouponService.php`](api/nuxnanravel/app/Services/CouponService.php))
   - `generateCouponCode()`: Generates unique 12-character coupon codes
   - `createPointsCoupon()`: Creates coupon from points (deducts from user balance)
   - `createWalletCoupon()`: Creates coupon from wallet (deducts from user balance)
   - `redeemCoupon()`: Redeems coupon by code (adds points/money to user)
   - `cancelCoupon()`: Cancels active coupon (refunds points/money)
   - `generateQRCode()`: Generates QR code for coupon
   - `getUserCoupons()`: Retrieves user's coupons with filters
   - `markExpiredCoupons()`: Marks expired coupons as expired

#### Controllers

**CouponController** ([`CouponController.php`](api/nuxnanravel/app/Http/Controllers/Api/CouponController.php))
   - `POST /api/coupons`: Create new coupon
   - `GET /api/coupons`: List user's coupons with filters
   - `GET /api/coupons/{id}`: Get coupon details
   - `GET /api/coupons/{id}/print`: Get printable coupon data
   - `POST /api/coupons/redeem`: Redeem coupon by code
   - `POST /api/coupons/{id}/cancel`: Cancel active coupon
   - `GET /api/coupons/statistics`: Get coupon statistics

#### Routes

**Coupon Routes** ([`api-coupons.php`](api/nuxnanravel/routes/api-coupons.php))
   - All routes protected with `auth:api` middleware
   - Prefix: `/api/coupons`
   - Included in main [`api.php`](api/nuxnanravel/routes/api.php)

### Frontend (Vue 3 + TypeScript)

#### Components

1. **CouponCreationForm** ([`CouponCreationForm.vue`](ui/components/coupons/CouponCreationForm.vue))
   - Form for creating new coupons
   - Toggle between points and wallet coupons
   - Amount input with validation
   - Optional expiration date input
   - Optional description input
   - Shows available balance
   - Loading states and error handling

2. **CouponCard** ([`CouponCard.vue`](ui/components/coupons/CouponCard.vue))
   - Displays coupon information
   - Shows QR code image
   - Displays coupon code with copy button
   - Shows coupon status with color coding
   - Shows creation and redemption dates
   - Print button for printable version
   - Cancel button for active coupons

3. **CouponList** ([`CouponList.vue`](ui/components/coupons/CouponList.vue))
   - Lists all user coupons
   - Filter by type (all/points/wallet)
   - Filter by status (all/active/redeemed/expired/cancelled)
   - Shows statistics bar
   - Empty state handling
   - Loading states

4. **CouponRedemption** ([`CouponRedemption.vue`](ui/components/coupons/CouponRedemption.vue))
   - Toggle between QR scan and code entry
   - QR code scanning with camera
   - Manual code entry with paste button
   - Redemption result display
   - New balance display
   - Information section with usage rules

#### Pages

1. **Coupons Index** ([`index.vue`](ui/pages/coupons/index.vue))
   - Main coupons page
   - Create coupon button
   - Coupon list display
   - Modal for coupon creation
   - Responsive design

2. **Coupons Redeem** ([`redeem.vue`](ui/pages/coupons/redeem.vue))
   - Coupon redemption page
   - Redemption component
   - Clear instructions
   - Gradient background design

## Dependencies

### Backend
- **simplesoftwareio/simple-qrcode**: QR code generation library
  - Added to [`composer.json`](api/nuxnanravel/composer.json)
  - Version: ^4.2

### Frontend
- **@zxing/library**: QR code scanning library (for camera scanning)
  - Dynamically imported in CouponRedemption component

## Security Features

1. **Unique Coupon Codes**
   - Cryptographically secure random generation
   - 12-character alphanumeric codes
   - Database-level uniqueness constraint

2. **Single-Use Redemption**
   - Each coupon can only be redeemed once
   - Status change prevents re-use

3. **Self-Redemption Prevention**
   - Users cannot redeem their own coupons
   - Server-side validation

4. **Expiration Support**
   - Optional expiration dates
   - Automatic expiration status updates
   - Expired coupons cannot be redeemed

5. **Audit Trail**
   - All redemptions tracked with:
     - IP address
     - User agent
     - Timestamp

6. **Transaction Integrity**
   - Database transactions for all operations
   - Atomic operations for balance updates
   - Rollback on failure

## API Endpoints

### Create Coupon
```
POST /api/coupons
Authorization: Bearer {token}
Content-Type: application/json

Request Body:
{
  "type": "points" | "wallet",
  "amount": number,
  "description": string (optional),
  "expires_in_days": number (optional, 1-365)
}

Response (201):
{
  "success": true,
  "message": "สร้างคูปองสำเร็จ",
  "data": {
    "coupon": { ... },
    "qr_code_url": "/storage/qr-codes/XXXXXXXXXXXX.png"
  }
}
```

### List Coupons
```
GET /api/coupons?type=points&status=active
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "data": {
    "coupons": [ ... ],
    "total": 10
  }
}
```

### Redeem Coupon
```
POST /api/coupons/redeem
Authorization: Bearer {token}
Content-Type: application/json

Request Body:
{
  "coupon_code": "XXXXXXXXXXXX"
}

Response (200):
{
  "success": true,
  "message": "รับแต้มสำเร็จ",
  "data": {
    "type": "points",
    "amount": 1000,
    "new_balance": 5000,
    "coupon": { ... }
  }
}
```

### Cancel Coupon
```
POST /api/coupons/{id}/cancel
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "ยกเลิกคูปองสำเร็จ"
}
```

### Get Statistics
```
GET /api/coupons/statistics
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "data": {
    "total_coupons": 15,
    "active_coupons": 8,
    "redeemed_coupons": 5,
    "cancelled_coupons": 2,
    "expired_coupons": 0,
    "points_coupons": 10,
    "wallet_coupons": 5,
    "total_points_in_coupons": 50000,
    "total_wallet_in_coupons": 250.00
  }
}
```

## Installation Instructions

### Backend Setup

1. **Install Dependencies**
   ```bash
   cd api/nuxnanravel
   composer require simplesoftwareio/simple-qrcode:^4.2
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```

3. **Clear Cache**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Verify Routes**
   ```bash
   php artisan route:list --path=coupons
   ```

### Frontend Setup

1. **Install QR Scanning Library**
   ```bash
   cd ui
   npm install @zxing/library
   ```

2. **Build Assets**
   ```bash
   npm run build
   ```

3. **Verify Components**
   - Check that all components are properly imported
   - Verify TypeScript types
   - Test component rendering

## Testing Checklist

### Unit Tests
- [ ] CouponService::generateCouponCode() generates unique codes
- [ ] CouponService::createPointsCoupon() deducts points correctly
- [ ] CouponService::createWalletCoupon() deducts money correctly
- [ ] CouponService::redeemCoupon() adds points/money correctly
- [ ] CouponService::cancelCoupon() refunds points/money correctly
- [ ] Coupon::canRedeem() returns correct boolean
- [ ] Coupon::isExpired() returns correct boolean

### Integration Tests
- [ ] Create points coupon via API
- [ ] Create wallet coupon via API
- [ ] List coupons with filters
- [ ] Redeem valid coupon code
- [ ] Redeem invalid coupon code
- [ ] Redeem expired coupon
- [ ] Redeem own coupon (should fail)
- [ ] Cancel active coupon
- [ ] Cancel redeemed coupon (should fail)
- [ ] Get coupon statistics

### End-to-End Tests
- [ ] Complete coupon creation flow (points)
- [ ] Complete coupon creation flow (wallet)
- [ ] Complete coupon redemption flow (QR scan)
- [ ] Complete coupon redemption flow (code entry)
- [ ] Complete coupon cancellation flow
- [ ] Print coupon functionality
- [ ] Filter coupons by type
- [ ] Filter coupons by status

## User Flow Examples

### Creating a Points Coupon
1. User navigates to `/coupons`
2. Clicks "สร้างคูปอง" button
3. Selects "คูปองแต้ม" type
4. Enters amount (e.g., 1000 points)
5. Optionally adds description
6. Optionally sets expiration (e.g., 30 days)
7. Clicks "สร้างคูปอง"
8. Points are deducted from balance
9. Coupon is created with QR code
10. User can print or share coupon

### Creating a Wallet Coupon
1. User navigates to `/coupons`
2. Clicks "สร้างคูปอง" button
3. Selects "คูปองเงิน" type
4. Enters amount (e.g., 50 THB)
5. Optionally adds description
6. Optionally sets expiration (e.g., 60 days)
7. Clicks "สร้างคูปอง"
8. Money is deducted from wallet
9. Coupon is created with QR code
10. User can print or share coupon

### Redeeming a Coupon (QR Scan)
1. User navigates to `/coupons/redeem`
2. Selects "สแกน QR Code" method
3. Clicks "เริ่มสแกน"
4. Grants camera permissions
5. Points camera at QR code
6. QR code is automatically detected
7. Coupon is redeemed
8. Points/money are added to balance
9. Success message is displayed
10. New balance is shown

### Redeeming a Coupon (Code Entry)
1. User navigates to `/coupons/redeem`
2. Selects "ป้อนรหัส" method
3. Enters 12-character code
4. Or clicks paste button
5. Clicks "รับคูปอง"
6. Coupon is validated
7. If valid, coupon is redeemed
8. Points/money are added to balance
9. Success message is displayed
10. New balance is shown

### Canceling a Coupon
1. User navigates to `/coupons`
2. Finds active coupon in list
3. Clicks "ยกเลิก" button on coupon card
4. Confirms cancellation
5. Coupon is marked as cancelled
6. Points/money are refunded to balance
7. Coupon can no longer be redeemed

## File Structure

### Backend
```
api/nuxnanravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── CouponController.php
│   ├── Models/
│   │   ├── Coupon.php
│   │   └── CouponRedemption.php
│   └── Services/
│       └── CouponService.php
├── database/
│   └── migrations/
│       ├── 2026_01_16_000001_create_coupons_table.php
│       └── 2026_01_16_000002_create_coupon_redemptions_table.php
└── routes/
    └── api-coupons.php
```

### Frontend
```
ui/
├── components/
│   └── coupons/
│       ├── CouponCreationForm.vue
│       ├── CouponCard.vue
│       ├── CouponList.vue
│       └── CouponRedemption.vue
└── pages/
    └── coupons/
        ├── index.vue
        └── redeem.vue
```

## Future Enhancements

1. **Bulk Coupon Creation**
   - Create multiple coupons at once
   - Batch operations for efficiency

2. **Coupon Templates**
   - Pre-defined coupon designs
   - Custom branding options

3. **Email/SMS Delivery**
   - Send coupons via email
   - Send coupons via SMS
   - Delivery tracking

4. **Coupon Analytics Dashboard**
   - Advanced usage statistics
   - Redemption trends
   - User behavior insights

5. **Admin Management Interface**
   - View all coupons system-wide
   - Manage expired coupons
   - Audit redemption logs
   - System-wide statistics

6. **Coupon Sharing**
   - Share coupons via social media
   - Generate shareable links
   - Track share analytics

7. **Advanced QR Features**
   - Custom QR code designs
   - Logo embedding
   - Color customization

8. **Multi-Language Support**
   - Full i18n implementation
   - Language switching
   - Localized error messages

## Notes

- All coupon operations require authentication
- All sensitive operations use database transactions
- QR codes are stored in `storage/app/public/qr-codes/`
- Coupon codes are case-insensitive for redemption
- Expiration dates are optional (null = no expiration)
- Users can cancel only their own active coupons
- Redemption attempts are logged for security auditing
- The system integrates seamlessly with existing PointsService and WalletService

## Conclusion

The coupon system has been successfully implemented with:
- Complete backend API with Laravel
- Comprehensive frontend components with Vue 3 + TypeScript
- QR code generation and scanning capabilities
- Full integration with existing points and wallet systems
- Security features including audit trails and single-use enforcement
- User-friendly interface with Thai language support

The system is ready for testing and deployment.
