<?php

// Import Controllers
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProviderAuthController;
use App\Http\Controllers\Api\Auth\StudentActivationController;
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupController;
use App\Http\Controllers\Api\Learn\Course\info\MentalMathController;
use App\Http\Controllers\Api\Learn\Student\Master\StudentController;
use App\Http\Controllers\Api\Play\ActivityController;
use App\Http\Controllers\Api\Play\BadgeController;
use App\Http\Controllers\Api\Play\EventController;
use App\Http\Controllers\Api\Play\FriendController;
use App\Http\Controllers\Api\Play\NewsfeedController;
use App\Http\Controllers\Api\Play\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\Shared\ForgotPasswordController;
use App\Http\Controllers\Api\Shared\SuggesterController;
use App\Http\Controllers\Api\Shared\UserProfileController;
use App\Http\Controllers\Api\Shopping\CartController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\WelcomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health check endpoint (safe for production)
Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
})->name('api.ping');

// Public Routes
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/welcome', [WelcomeController::class, 'index'])->name('api.welcome');

Route::get('/register/{user:reference_code}', [SuggesterController::class, 'index'])->name('register.reference');
Route::get('/suggester/check/{user:personal_code}', [SuggesterController::class, 'checkSuggesterExist'])->name('suggester.check');
Route::get('/check-username-exists/{name}', [UserProfileController::class, 'checkUsernameExists'])->name('profile.username.check');
Route::get('/check-email-exists/{email}', [UserProfileController::class, 'checkEmailExists'])->name('profile.email.check');

Route::get('/mental-math', [MentalMathController::class, 'index'])->name('mental-math');

// Auth Routes (API)
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated. Please login first.',
    ], 401);
})->name('api.login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/validate-referral-code', [AuthController::class, 'validateReferralCode']);

// Student Account Activation (public — no auth required)
Route::get('/student-activate/{token}', [StudentActivationController::class, 'show']);
Route::post('/student-activate/{token}', [StudentActivationController::class, 'activate']);
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});

// OAuth Routes (require web middleware for session/state)
Route::middleware('web')->group(function () {
    Route::get('/auth/{provider}/redirect', [ProviderAuthController::class, 'redirectToGoogle'])->name('oauth.redirect');
    Route::get('/auth/{provider}/callback', [ProviderAuthController::class, 'handleGoogleCallback'])->name('oauth.callback');
});

// Protected Routes
Route::middleware(['auth:api'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::post('/profile', [SettingsController::class, 'updateProfile']);
        Route::post('/account', [SettingsController::class, 'updateAccount']);
        Route::post('/password', [SettingsController::class, 'updatePassword']);
        Route::post('/avatar', [SettingsController::class, 'updateAvatar']);
        Route::post('/cover', [SettingsController::class, 'updateCover']);
    });

    Route::get('/dashboard', function () {
        return response()->json([
            'isPlearndAdmin' => auth()->user()->isPlearndAdmin(),
        ]);
    })->name('dashboard');

    Route::get('/newsfeed', [NewsfeedController::class, 'index'])->name('newsfeed');
    Route::get('/newsfeed/activities', [ActivityController::class, 'newsfeed'])->name('newsfeed.activities');
    Route::apiResource('activities', ActivityController::class)->only(['index', 'show', 'destroy']);
    Route::get('/users/{user:reference_code}/profile', [UserProfileController::class, 'index'])->name('user.profile');

    // Profile Management Routes
    Route::prefix('profile')->group(function () {
        Route::get('/me', [UserProfileController::class, 'me'])->name('profile.me');
        Route::put('/update', [UserProfileController::class, 'update'])->name('profile.update');
        Route::post('/avatar', [UserProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::post('/cover', [UserProfileController::class, 'updateCover'])->name('profile.cover');
        Route::get('/completion', [UserProfileController::class, 'completion'])->name('profile.completion');
        Route::put('/privacy', [UserProfileController::class, 'updatePrivacy'])->name('profile.privacy');
        Route::get('/stats', [UserProfileController::class, 'stats'])->name('profile.stats');
    });

    // User search for transfer (must be before {identifier} routes)
    Route::get('/users/search', [UserProfileController::class, 'search'])->name('users.search');

    // User profile by identifier (supports ID, reference_code, or username)
    Route::get('/users/{identifier}/show', [UserProfileController::class, 'show'])->name('user.profile.show');
    Route::get('/users/{identifier}/stats', [UserProfileController::class, 'stats'])->name('user.stats');
    Route::get('/users/{identifier}/activities', [UserProfileController::class, 'activities'])->name('user.activities');

    // Forgot Password (Authenticated?) - Logic from old web.php seems to have it under auth:sanctum?
    // Usually forgot password is public, but let's keep it as is if that's what it was,
    // OR move it to public if it was a mistake.
    // Looking at lines 37-43 in old web.php, it IS under auth:sanctum.
    // But wait, forgot password usually implies you can't login.
    // Maybe it's for "Reset Password" while logged in? Or the old app had weird grouping.
    // Let's keep it here for now but verify later.
    Route::get('/forgot-password', [ForgotPasswordController::class, 'index'])->name('forgot-pasword');
    Route::post('/forgot-password/getuser', [ForgotPasswordController::class, 'getUser'])->name('forgot-pasword.get-user');
    Route::post('/forgot-password/reset/{user}', [ForgotPasswordController::class, 'resetPassword'])->name('forgot-pasword.reset');
    Route::post('/forgot-password/exchange/{user}', [ForgotPasswordController::class, 'exchangeMoney'])->name('forgot-pasword.exchange');
    Route::delete('/forgot-password/users/{user}', [ForgotPasswordController::class, 'destroy']);

    // Cart Routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);

    // User Groups Routes
    Route::get('/users/{user}/groups', [AcademyGroupController::class, 'getUserGroups']);
    Route::get('/profile/groups', function (Request $request) {
        return app(AcademyGroupController::class)->getUserGroups($request->user());
    });

    // Events Routes
    Route::get('/users/{user}/events', [EventController::class, 'getUserEvents']);
    Route::get('/profile/events', function (Request $request) {
        return app(EventController::class)->getUserEvents($request->user());
    });

    // Badges Routes
    Route::get('/users/{user}/badges', [BadgeController::class, 'getUserBadges']);
    Route::get('/profile/badges', function (Request $request) {
        return app(BadgeController::class)->getUserBadges($request->user());
    });

    // Friends
    Route::get('/friends/suggestions', [FriendController::class, 'suggestions'])->name('friends.suggestions');
    Route::get('/friends/pending', [FriendController::class, 'pendingRequests'])->name('friends.pending');
    Route::get('/friends/search', [FriendController::class, 'search'])->name('friends.search');
    Route::post('/friends/{recipient}', [FriendController::class, 'addFriendRequest'])->name('friend-request');
    Route::delete('/friends/{friend}', [FriendController::class, 'deleteFriendRequest'])->name('delete-friend-request');
    Route::patch('/friends/{friend}/accept', [FriendController::class, 'acceptFriendRequest'])->name('accept-friend-request');
    Route::post('/friends/{friend}/deny', [FriendController::class, 'denyFriendRequest'])->name('deny-friend-request');
    Route::post('/friends/{friend}/unfriend', [FriendController::class, 'unfriend'])->name('unfriend');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends');
    Route::get('/users/{identifier}/friends', [FriendController::class, 'userFriends'])->name('user.friends');

    // ===========================================
    // Notifications
    // ===========================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/read', [NotificationController::class, 'deleteAllRead'])->name('notifications.delete-read');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // Super Admin Check (any authenticated user can check their own status)
    Route::get('/super-admins/check', [SuperAdminController::class, 'check'])->name('super-admin.check');

});

// Super Admin Management Routes (requires Super Admin privileges)
Route::middleware(['auth:api', 'super-admin'])->prefix('super-admins')->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('super-admin.index');
    Route::post('/', [SuperAdminController::class, 'store'])->name('super-admin.store');
    Route::delete('/{userId}', [SuperAdminController::class, 'destroy'])->name('super-admin.destroy');
});

// Include other route files
require __DIR__.'/earn/donate.php';
require __DIR__.'/earn/advert.php';
require __DIR__.'/earn/campaign.php';
require __DIR__.'/earn/points-wallet.php';
require __DIR__.'/earn/coupons.php';
require __DIR__.'/earn/qr.php';
require __DIR__.'/play/post.php';
require __DIR__.'/play/game.php';
require __DIR__.'/play/shares.php';
require __DIR__.'/play/photos.php';
require __DIR__.'/play/videos.php';
require __DIR__.'/learn/academy.php';
require __DIR__.'/learn/course.php';
require __DIR__.'/learn/student.php';

// Academy-based Student Card and Home Visit Routes (NEW - under academy management)
require __DIR__.'/learn/academy-student-card.php';
require __DIR__.'/learn/academy-student-card-request.php';
require __DIR__.'/learn/academy-home-visit.php';

// Student Profile Routes (view student profile by academy context)
require __DIR__.'/learn/student-profile.php';

// Academy Store Management Routes (School Store System)
require __DIR__.'/learn/academy-store.php';
require __DIR__.'/public/courses.php';

// Student Master Profile Routes
Route::middleware(['auth:api'])->prefix('student')->group(function () {
    Route::get('/me', [StudentController::class, 'myProfile']);
    Route::prefix('requests')->group(function () {
        Route::get('/', [StudentController::class, 'listRequests']);
        Route::patch('/{id}/approve', [StudentController::class, 'approveRequest']);
        Route::patch('/{id}/reject', [StudentController::class, 'rejectRequest']);
    });

    Route::prefix('master')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::get('/{student}', [StudentController::class, 'show']);
    });
});

// Legacy routes (deprecated - kept for backward compatibility)
// GUARD: Do NOT add new routes here. Use /learn/academy-home-visit.php or unified master profile instead.
// TODO: Remove these in future version after frontend migration is complete
require __DIR__.'/homevisit/homevisit.php';
require __DIR__.'/studentcard/studentcard.php';

// Note: Admin routes are loaded in bootstrap/app.php with /api/admin prefix
// Do not include them here to avoid route conflicts

// NOTE: Debug routes were removed for security.
// ปลอดภัยกว่าในการทำ debug ผ่าน `artisan tinker` / local-only routes
// ถ้าจำเป็น ให้สร้าง routes ใน routes/local.php ที่โหลดเฉพาะ
// `app()->environment('local')` ไม่ใช่ `env('APP_DEBUG')` เพราะ APP_DEBUG
// อาจถูกเปิดชั่วคราวใน production เพื่อ debug error
