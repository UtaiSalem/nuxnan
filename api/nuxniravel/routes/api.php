<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Controllers
use App\Http\Controllers\Api\WelcomeController;
use App\Http\Controllers\Api\Shared\SuggesterController;
use App\Http\Controllers\Api\Shared\UserProfileController;
use App\Http\Controllers\Api\Shared\ForgotPasswordController;
use App\Http\Controllers\Api\Play\FriendController;
use App\Http\Controllers\Api\Play\NewsfeedController;
use App\Http\Controllers\Api\Play\ActivityController;
use App\Http\Controllers\Api\Learn\Course\info\MentalMathController;
use App\Http\Controllers\Api\Auth\ProviderAuthController;
use App\Http\Controllers\Api\Auth\AuthController;

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

Route::get('/user1', function () {
    $user1 = \App\Models\User::find(2);
    $user1Resource = new \App\Http\Resources\UserResource($user1);
    return response()->json([
        'user' => $user1Resource
    ]);
});

// Public Routes
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/welcome', [WelcomeController::class, 'index'])->name('api.welcome');
Route::get('/register/{user:reference_code}', [SuggesterController::class, 'index'])->name('register.reference');
Route::get('/suggester/check/{user:personal_code}', [SuggesterController::class, 'checkSuggesterExist'])->name('suggester.check');
Route::get('/check-username-exists/{name}', [UserProfileController::class, 'checkUsernameExists'])->name('profile.username.check');
Route::get('/check-email-exists/{email}', [UserProfileController::class, 'checkEmailExists'])->name('profile.email.check');

Route::get('/mental-math', [MentalMathController::class, 'index'])->name('mental-math');

Route::get('/debug-data', function () {
    return response()->json([
        'user_id' => auth()->id(),
        'activity_count' => \App\Models\Activity::count(),
        'post_count' => \App\Models\Post::count(),
        'my_activities' => \App\Models\Activity::where('user_id', auth()->id())->get(),
        'all_activities' => \App\Models\Activity::latest()->take(5)->get(),
    ]);
})->middleware('auth:api');

// Simple ping test
Route::get('/ping', function() {
    \Log::info('Ping endpoint called');
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel is working!',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Test endpoint for debugging login (remove in production)
if (env('APP_DEBUG')) {
    Route::post('/test-login-now', function (Request $request) {
        $loginInput = $request->input('login', '0938403000');
        $password = $request->input('password', 'password');
        
        $user = \App\Models\User::where('email', $loginInput)
            ->orWhere('phone_number', $loginInput)
            ->orWhere('personal_code', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();
        
        $result = ['input' => $loginInput, 'user_found' => $user ? true : false];
        
        if ($user) {
            $result['user'] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'has_password' => !empty($user->password),
            ];
            
            $passwordMatch = \Hash::check($password, $user->password);
            $result['password_matches'] = $passwordMatch;
            
            if ($passwordMatch) {
                try {
                    $token = \Auth::guard('api')->login($user);
                    $result['token_created'] = $token ? true : false;
                    if ($token) {
                        $result['token'] = substr($token, 0, 30) . '...';
                        \Auth::guard('api')->logout();
                    }
                } catch (\Exception $e) {
                    $result['token_error'] = $e->getMessage();
                }
            }
        }
        
        return response()->json($result, 200);
    });
}

// Auth Routes (API)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/validate-referral-code', [AuthController::class, 'validateReferralCode']);
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

    Route::get('/dashboard', function () {
        return response()->json([
            'isPlearndAdmin' => auth()->user()->isPlearndAdmin()
        ]);
    })->name('dashboard');
    
    Route::get('/newsfeed', [NewsfeedController::class, 'index'] )->name('newsfeed');
    Route::get('/newsfeed/activities', [ActivityController::class, 'newsfeed'] )->name('newsfeed.activities');
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

    // Friends
    Route::get('/friends/suggestions', [FriendController::class, 'suggestions'])->name('friends.suggestions');
    Route::get('/friends/pending', [FriendController::class, 'pendingRequests'])->name('friends.pending');
    Route::post('/friends/{recipient}', [FriendController::class, 'addFriendRequest'])->name('friend-request');
    Route::delete('/friends/{friend}', [FriendController::class, 'deleteFriendRequest'])->name('delete-friend-request');   
    Route::patch('/friends/{friend}/accept', [FriendController::class, 'acceptFriendRequest'])->name('accept-friend-request');
    Route::post('/friends/{friend}/deny', [FriendController::class, 'denyFriendRequest'])->name('deny-friend-request');
    Route::post('/friends/{friend}/unfriend', [FriendController::class, 'unfriend'])->name('unfriend');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends');

    // Super Admin Check (any authenticated user can check their own status)
    Route::get('/super-admins/check', [\App\Http\Controllers\Api\SuperAdminController::class, 'check'])->name('super-admin.check');

});

// Super Admin Management Routes (requires Super Admin privileges)
Route::middleware(['auth:api', 'super-admin'])->prefix('super-admins')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SuperAdminController::class, 'index'])->name('super-admin.index');
    Route::post('/', [\App\Http\Controllers\Api\SuperAdminController::class, 'store'])->name('super-admin.store');
    Route::delete('/{userId}', [\App\Http\Controllers\Api\SuperAdminController::class, 'destroy'])->name('super-admin.destroy');
});

// Include other route files
require __DIR__ . '/earn/donate.php';
require __DIR__ . '/earn/advert.php';
require __DIR__ . '/play/post.php';
require __DIR__ . '/play/game.php';
require __DIR__ . '/learn/academy.php';
require __DIR__ . '/learn/course.php';
require __DIR__ . '/learn/student.php';
require __DIR__ . '/homevisit/homevisit.php';
require __DIR__ . '/studentcard/studentcard.php';

// Share system routes
require __DIR__ . '/api_shares.php';

// Debug routes (remove in production)
if (env('APP_DEBUG')) {
    require __DIR__ . '/debug_login.php';
    require __DIR__ . '/test_simple.php';
}
