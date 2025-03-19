<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\WatchHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('google/callback', [AuthController::class, 'googleCallback']);
});

// Payment callback routes (public but secured by signature)
Route::group(['prefix' => 'payments'], function () {
    Route::post('callback', [PaymentController::class, 'handleCallback'])->name('api.payments.callback');
    Route::get('cancel', [PaymentController::class, 'handleCancellation'])->name('api.payments.cancel');
});

// Protected routes
Route::group(['middleware' => 'jwt'], function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Payment and subscription routes
    Route::group(['prefix' => 'payments'], function () {
        Route::post('initialize', [PaymentController::class, 'initializePayment']);
        Route::post('verify', [PaymentController::class, 'verifyPayment']);
        Route::get('history', [PaymentController::class, 'getPaymentHistory']);
        Route::get('subscription/details', [PaymentController::class, 'getSubscriptionDetails']);
        Route::get('subscription/plans', [PaymentController::class, 'getSubscriptionPlans']);
    });

    // Channel routes
    Route::group(['prefix' => 'channels'], function () {
        Route::get('/', [ChannelController::class, 'index']);
        Route::get('/categories', [ChannelController::class, 'getCategories']);
        Route::get('/popular', [ChannelController::class, 'getPopularChannels']);
        Route::get('/recommended', [ChannelController::class, 'getRecommendedChannels']);
        Route::get('/{channel}', [ChannelController::class, 'show']);
        Route::get('/{channel}/stream', [ChannelController::class, 'getStreamingUrl']);
    });

    // Advertisement routes
    Route::group(['prefix' => 'ads'], function () {
        Route::get('/next', [AdvertisementController::class, 'getNextAd']);
        Route::get('/types', [AdvertisementController::class, 'getTypes']);
        Route::get('/positions', [AdvertisementController::class, 'getPositions']);
        Route::post('/{advertisement}/click', [AdvertisementController::class, 'recordClick']);
    });

    // Watch History routes
    Route::group(['prefix' => 'history'], function () {
        Route::get('/', [WatchHistoryController::class, 'index']);
        Route::post('/channels/{channel}', [WatchHistoryController::class, 'store']);
        Route::put('/{history}/duration', [WatchHistoryController::class, 'updateDuration']);
        Route::get('/statistics', [WatchHistoryController::class, 'getUserStatistics']);
        Route::get('/channels/{channel}', [WatchHistoryController::class, 'getChannelHistory']);
        Route::get('/recent', [WatchHistoryController::class, 'getRecentlyWatched']);
        Route::delete('/', [WatchHistoryController::class, 'clearHistory']);
    });
});

// Admin routes
Route::group(['prefix' => 'admin', 'middleware' => ['jwt', 'admin']], function () {
    // Payment management
    Route::get('payments/statistics', [PaymentController::class, 'getPaymentStatistics']);
    
    // Channel management
    Route::group(['prefix' => 'channels'], function () {
        Route::get('/statistics', [ChannelController::class, 'getStatistics']);
        Route::post('/', [ChannelController::class, 'store']);
        Route::put('/{channel}', [ChannelController::class, 'update']);
        Route::delete('/{channel}', [ChannelController::class, 'destroy']);
    });

    // Advertisement management
    Route::group(['prefix' => 'ads'], function () {
        Route::get('/statistics', [AdvertisementController::class, 'getStatistics']);
        Route::get('/schedule', [AdvertisementController::class, 'getSchedule']);
        Route::post('/', [AdvertisementController::class, 'store']);
        Route::put('/{advertisement}', [AdvertisementController::class, 'update']);
        Route::delete('/{advertisement}', [AdvertisementController::class, 'destroy']);
    });

    // Watch History management
    Route::group(['prefix' => 'history'], function () {
        Route::get('/statistics', [WatchHistoryController::class, 'getGlobalStatistics']);
    });
});