<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;

use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Public\ProductController as PublicProductController;

use App\Http\Controllers\Api\Public\CategoryController;
use App\Http\Controllers\Api\Public\CurrencyController;

use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\InventoryController;

use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;

use App\Http\Controllers\Api\Admin\DashboardController;

use App\Http\Controllers\Api\Customer\ProfileController;
use App\Http\Controllers\Api\Customer\AddressController;

use App\Http\Controllers\Api\Payment\HitPayController;

use App\Http\Controllers\Api\Admin\CustomerController;

use App\Http\Controllers\Api\Admin\StoreSettingController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Customer\SettingController;
use App\Http\Controllers\Api\MembershipApplicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as FacadesRoute;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/logout', [AuthController::class, 'logout']);

    });

});

/*
|--------------------------------------------------------------------------
| Email Verification (API)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/email/verification/send', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully.',
        ]);
    });

    Route::post('/email/verification/resend', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:1,1');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $user = User::findOrFail($request->route('id'));

    if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if ($request->hasValidSignature() && ! $user->hasVerifiedEmail()) {
        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }

    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    return redirect($frontendUrl . '/email-verified');
})->middleware('signed')->name('verification.verify');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');
Route::post('/membership/apply', [MembershipApplicationController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);
Route::get('/currencies', CurrencyController::class);

/*
|--------------------------------------------------------------------------
| Public Products
|--------------------------------------------------------------------------
*/

Route::get('/products/search', [PublicProductController::class, 'search']);

Route::get('/products', [PublicProductController::class, 'index']);

Route::get('/products/category/{category}', [PublicProductController::class, 'category']);

Route::get('/products/{slug}', [PublicProductController::class, 'show']);

Route::get('/products/{slug}/related', [PublicProductController::class, 'related']);
Route::get('/store-status', [StoreSettingController::class, 'publicStatus']);

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        Route::get(

            'dashboard',

            [DashboardController::class,'index']

        );

        Route::post(
            'products/bulk-duplicate',
            [AdminProductController::class, 'bulkDuplicate']
        );

        Route::patch(
            'products/bulk-status',
            [AdminProductController::class, 'bulkStatus']
        );

        Route::post(
            'products/{product}/duplicate',
            [AdminProductController::class, 'duplicate']
        );

        Route::apiResource(
            'products',
            AdminProductController::class
        );

        Route::get(
            'customers',
            [CustomerController::class, 'index']
        );

        Route::get(
            'products/{product}/images',
            [ProductImageController::class, 'index']
        );

        Route::post(
            'products/{product}/images',
            [ProductImageController::class, 'store']
        );

        Route::delete(
            'products/images/{image}',
            [ProductImageController::class, 'destroy']
        );

        Route::patch(
            'products/images/{image}/primary',
            [ProductImageController::class, 'setPrimary']
        );

        Route::patch(
            'products/{product}/images/reorder',
            [ProductImageController::class, 'reorder']
        );

        Route::get(
            'products/{product}/inventory',
            [InventoryController::class, 'history']
        );

        Route::post(
            'products/{product}/inventory',
            [InventoryController::class, 'store']
        );

        Route::get(
            'orders',
            [AdminOrderController::class, 'index']
        );

        Route::get(
            'orders/{order}',
            [AdminOrderController::class, 'show']
        );

        Route::patch(
            'orders/{order}/status',
            [AdminOrderController::class, 'updateStatus']
        );

        Route::get(
            'store-settings',
            [StoreSettingController::class,'show']
        );

        Route::put(
            'store-settings',
            [StoreSettingController::class,'update']
        );

        Route::get(
            'notifications',
            [NotificationController::class, 'index']
        );

        Route::patch(
            'notifications/{notification}/read',
            [NotificationController::class, 'markAsRead']
        );

        Route::post(
            'notifications/read-all',
            [NotificationController::class, 'markAllAsRead']
        );
    }); 
/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get(

        '/profile',

        [ProfileController::class,'show']

    );

    Route::patch(

        '/profile',

        [ProfileController::class,'update']

    );

    Route::post(

        '/profile/avatar',

        [ProfileController::class,'updateAvatar']

    );

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);

});

/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        Route::prefix('cart')->group(function () {

            Route::get('/', [CartController::class, 'index']);

            Route::post('/', [CartController::class, 'store']);

            Route::patch('/{cartItem}', [CartController::class, 'update']);

            Route::delete('/{cartItem}', [CartController::class, 'destroy']);

            Route::delete('/', [CartController::class, 'clear']);

        });

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/checkout',
            [CheckoutController::class, 'store']
        );

        /*
        |--------------------------------------------------------------------------
        | Customer Orders
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/orders',
            [CustomerOrderController::class, 'index']
        );

        Route::get(
            '/orders/{order}',
            [CustomerOrderController::class, 'show']
        );

        /*
        |--------------------------------------------------------------------------
        | Customer Settings
        |--------------------------------------------------------------------------
        */

    Route::get(

        '/settings',

        [SettingController::class,'show']

    );

    Route::patch(

        '/settings',

        [SettingController::class,'update']

    );

    Route::patch(

        '/settings/password',

        [SettingController::class,'updatePassword']

    );

});

/*
|--------------------------------------------------------------------------
| HitPay
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        '/payments/create',
        [HitPayController::class, 'create']
    );

    Route::post(
        '/payments/hitpay',
        [HitPayController::class, 'create']
    );

});

Route::post(
    '/payments/webhook',
    [HitPayController::class, 'webhook']
);

Route::get(
    '/payments/callback',
    [HitPayController::class, 'callback']
);

Route::middleware('auth:sanctum')->get('/debug-user', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'user' => $request->user(),
    ]);
});
