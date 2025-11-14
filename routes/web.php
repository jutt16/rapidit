<?php

use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CookPricingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaticPageController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\WithdrawalAdminController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\PartnerRechargeController;
use App\Http\Controllers\RazorpayPaymentController;

Route::get('/', function () {
    // return redirect()->route('admin.login');
    return view('home.index');
})->name('home');

Route::get('/login', function (){
    return redirect()->route('home');
})->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::middleware('auth', 'admin')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/search', [SearchController::class, 'index'])->name('search');

        // Export routes
        Route::get('/users/export/csv', [UserController::class, 'export'])->name('users.export');
        Route::get('/partners/export/csv', [PartnerController::class, 'export'])->name('partners.export');
        Route::get('/bookings/export/csv', [BookingController::class, 'export'])->name('bookings.export');
        Route::get('/withdrawals/export/csv', [WithdrawalAdminController::class, 'export'])->name('withdrawals.export');
        Route::get('/reviews/export/csv', [ReviewController::class, 'export'])->name('reviews.export');
        Route::get('/support/export/csv', [SupportController::class, 'export'])->name('support.export');

        Route::resource('users', UserController::class);

        Route::resource('notifications', NotificationController::class);

        Route::resource('bookings', BookingController::class);

        Route::post('notifications/{notification}/resend', [NotificationController::class, 'resend'])->name('notifications.resend');

        Route::resource('banners', BannerController::class);

        Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::get('partners/{user}', [PartnerController::class, 'show'])->name('partners.show');
        Route::post('partners/{user}/status', [PartnerController::class, 'updateStatus'])->name('partners.updateStatus');

        Route::prefix('cook-pricings')->name('cook-pricings.')->group(function () {
            Route::get('/', [CookPricingController::class, 'index'])->name('index');
            Route::post('/save', [CookPricingController::class, 'save'])->name('save');
        });

        Route::resource('maid-pricings', \App\Http\Controllers\Admin\MaidPricingController::class);

        Route::resource('services', ServiceController::class);

        Route::resource('zones', ZoneController::class)->except(['show']);

        // Static Pages
        Route::get('/static-pages', [StaticPageController::class, 'index'])->name('static-pages.index');
        Route::get('/static-pages/{id}/edit', [StaticPageController::class, 'edit'])->name('static-pages.edit');
        Route::post('/static-pages/{id}', [StaticPageController::class, 'update'])->name('static-pages.update');

        // Support Messages
        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::get('/support/{id}', [SupportController::class, 'show'])->name('support.show');

        // Reviews
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
        Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{id}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

        // routes/admin.php or routes/web.php
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'edit'])->name('edit');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
        });

        // list + show (GET)
        Route::get('withdrawals', [WithdrawalAdminController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/{id}', [WithdrawalAdminController::class, 'show'])->name('withdrawals.show');

        // actions removed: admin can only view history

        // logout route
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::get('razorpay-payment/{id}', [RazorpayPaymentController::class, 'index'])
    ->withoutMiddleware(['*'])
    ->name('razorpay.pay');

Route::post('razorpay-verify', [RazorpayPaymentController::class, 'verifyPayment'])
    ->name('razorpay.verify');

Route::post('razorpay-callback/{payment_data}', [RazorpayPaymentController::class, 'handleCallback'])
    ->name('payments.callback');

Route::get('/razorpay/status/{booking}', [RazorpayPaymentController::class, 'status'])
    ->name('razorpay.status');

Route::get('/partner/recharge/{id}', [PartnerRechargeController::class, 'index'])
    ->name('recharge.pay');

Route::post('/partner/recharge/callback/{user}', [PartnerRechargeController::class, 'handleCallback'])
    ->name('recharge.callback');

Route::get('/partner/recharge/status/{id}', [PartnerRechargeController::class, 'status'])
    ->name('recharge.status');
