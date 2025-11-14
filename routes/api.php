<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankingDetailController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingPaymentController;
use App\Http\Controllers\Api\BookingRequestController;
use App\Http\Controllers\Api\BookingStatsController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartnerAvailabilityController;
use App\Http\Controllers\Api\PartnerLocationController;
use App\Http\Controllers\Api\PartnerPreferenceController;
use App\Http\Controllers\Api\PartnerProfileController;
use App\Http\Controllers\Api\PartnerReviewController;
use App\Http\Controllers\Api\PartnerStatsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServicePriceController;
use App\Http\Controllers\Api\StaticPageController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayoutWebhookController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ZoneController as ApiZoneController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('media/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        return response()->json(['message' => 'File not found.'], 404);
    }

    return response()->file($fullPath); // 👈 actually returns the file itself
})->where('path', '.*')->name('media');

Route::post('/payouts/webhook', [PayoutWebhookController::class, 'handle'])->name('payouts.webhook');
Route::post('/payouts/sync/{withdrawalId}', [PayoutWebhookController::class, 'syncStatus'])->name('payouts.sync');

Route::get('/zones', [ApiZoneController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    // Categories with services
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Partner Profile
    Route::get('/partner/profiles', [PartnerProfileController::class, 'index']);
    Route::post('/partner/profile', [PartnerProfileController::class, 'store']);
    Route::get('/partner/profile', [PartnerProfileController::class, 'show']);
    Route::post('/partner/profile/update', [PartnerProfileController::class, 'update']);

    Route::get('/user/profile', [UserProfileController::class, 'get']);
    Route::post('/user/profile', [UserProfileController::class, 'update']);

    Route::get('/partner/preferences', [PartnerPreferenceController::class, 'index']);
    Route::post('/partner/preferences', [PartnerPreferenceController::class, 'store']);
    Route::put('/partner/preferences/{service_id}', [PartnerPreferenceController::class, 'update']);
    Route::delete('/partner/preferences/{service_id}', [PartnerPreferenceController::class, 'destroy']);

    Route::get('/banners', [BannerController::class, 'activeBanners']);

    Route::post('addresses', [UserAddressController::class, 'store']); // Create
    Route::get('addresses', [UserAddressController::class, 'index']);  // List
    Route::put('addresses/{address}', [UserAddressController::class, 'update']); // Update
    Route::delete('addresses/{address}', [UserAddressController::class, 'destroy']); // Delete

    Route::post('/partner/availability', [PartnerAvailabilityController::class, 'store']);
    Route::get('/partner/me/availability', [PartnerAvailabilityController::class, 'me']);
    Route::patch('partner/availability/toggle', [PartnerAvailabilityController::class, 'toggle']);

    Route::get('/maids/prices', [ServicePriceController::class, 'getMaidPrices']);
    Route::post('/cook/calculate', [ServicePriceController::class, 'calculateCookPrice']);

    // Booking routes
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings/create', [BookingController::class, 'store']);
    Route::post('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/booking/{id}/completed', [BookingController::class,'completed']);
    Route::post('/bookings/{id}/mark-arrival', [BookingRequestController::class, 'markArrival']);

    // Booking Requests
    Route::get('/booking-requests', [BookingRequestController::class, 'index']);
    Route::post('/booking-requests/{id}/accept', [BookingRequestController::class, 'accept']);
    Route::post('/booking-requests/{id}/reject', [BookingRequestController::class, 'reject']);

    // Booking cancellation
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/cancellations', [BookingController::class, 'cancellations']);

    Route::get('/wallet', [WalletController::class, 'show']);

    // Payment routes
    // Initiate payment (COD or create razorpay link)

    // Poll payment status
    Route::get('/bookings/{booking}/payment-status', [BookingPaymentController::class, 'status']);

    // support
    Route::post('/support/contact', [SupportController::class, 'contact']);

    // create review for booking
    Route::post('/bookings/{booking}/reviews', [ReviewController::class, 'store']);
    // update (optional)
    Route::put('/bookings/{booking}/reviews/{id}', [ReviewController::class, 'update']);

    Route::post('/bookings/{booking}/pay', [BookingPaymentController::class, 'pay']);

    // Route::post('/bookings/{booking}/mark-paid', [BookingPaymentController::class,'markPaid']);
    // Route::match(['get', 'post'], '/payments/callback', [BookingPaymentController::class, 'callback'])
    //     ->name('payments.callback');

    Route::get('/bookings/{booking}/payment-status', [BookingPaymentController::class, 'status']);

    Route::post('/payments/webhook', [BookingPaymentController::class, 'webhook'])->name('payments.webhook');

    Route::get('/partner/stats', [PartnerStatsController::class, 'stats']);

    Route::get('/maid/average-rating', [PartnerStatsController::class, 'maidAverageRating']);

    Route::get('/partner/reviews', [PartnerReviewController::class, 'index']);

    // Static pages
    Route::get('/static/{slug}', [StaticPageController::class, 'show']);

    // banking details
    Route::get('banking-details', [BankingDetailController::class,'index']);
    Route::post('banking-details', [BankingDetailController::class,'store']);
    Route::get('banking-details/{id}', [BankingDetailController::class,'show']);
    Route::put('banking-details/{id}', [BankingDetailController::class,'update']);
    Route::delete('banking-details/{id}', [BankingDetailController::class,'destroy']);
    Route::post('banking-details/{id}/set-default', [BankingDetailController::class,'setDefault']);

    // withdrawals
    Route::get('withdrawals', [WithdrawalController::class,'index']);
    Route::post('withdrawals', [WithdrawalController::class,'store']);
    Route::get('withdrawals/{id}', [WithdrawalController::class,'show']);
    Route::post('withdrawals/{id}/cancel', [WithdrawalController::class,'cancel']);

    Route::post('/delete-profile', [UserProfileController::class, 'deleteProfile']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Booking Stats (Last 24 hours)
    Route::get('/bookings/stats/last-24-hours', [BookingStatsController::class, 'last24HoursCount']);
    Route::get('/partner/bookings/stats/last-24-hours', [BookingStatsController::class, 'partnerLast24HoursCount']);
    Route::get('/admin/bookings/stats/last-24-hours', [BookingStatsController::class, 'systemLast24HoursCount']);

    // New Booking Stats APIs
    Route::get('/bookings/stats/overall', [BookingStatsController::class, 'overallLast24Hours']);
    Route::post('/bookings/stats/nearby', [BookingStatsController::class, 'nearbyBookingsCount']);

    // Partner Location APIs
    Route::post('/experts/near-you/count', [PartnerLocationController::class, 'expertsNearYouCount']);
    Route::post('/experts/near-you/list', [PartnerLocationController::class, 'expertsNearYouList']);
    Route::post('/maid/nearest-price', [PartnerLocationController::class, 'nearestMaidStartingPrice']);

    // Random Reviews
    Route::get('/reviews/random', [ReviewController::class, 'randomReviews']);

    Route::get('/get-settings', [SettingController::class, 'getSettings']);
});

// Public endpoints (Razorpay will call / redirect)
// Route::get('/bookings/{booking}/pay', [BookingPaymentController::class, 'pay']);
// Route::get('/payments/callback', [BookingPaymentController::class, 'callback'])->name('payments.callback');
// Route::post('/payments/webhook', [BookingPaymentController::class, 'webhook'])->name('payments.webhook');
