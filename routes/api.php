<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\AdminPharmacyController;
use App\Http\Controllers\Api\PharmacyListingController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\NotificationController;

use App\Http\Controllers\Api\ReminderController;

use App\Http\Controllers\Api\HelpController;

use App\Http\Controllers\Api\SiteContentController;
use App\Http\Controllers\Api\SocialLinkController;

use App\Http\Controllers\Api\ContactQueryController;

use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\TranslationController;



use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ServiceController;

use App\Http\Controllers\api\InsightController;

use App\Http\Controllers\Admin\AppSettingController;
 
 
Route::get('/insights', [InsightController::class, 'index']);




/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:otp-send');
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->post('/save-fcm-token', [AuthController::class, 'saveFcmToken']);

/*
|--------------------------------------------------------------------------
| PROFILE + VENDOR ROUTES (Authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/profile/complete', [ProfileController::class, 'completeProfile']);
    Route::post('/update-profile', [ProfileController::class, 'setupProfile']);
    Route::get('/profile', [ProfileController::class, 'getProfile']);

    // Vendor pharmacy
    Route::get('/vendor/pharmacy', [PharmacyController::class, 'show']);
    Route::post('/vendor/pharmacy', [PharmacyController::class, 'store']);
    Route::post('/vendor/pharmacy/documents', [PharmacyController::class, 'uploadDocuments']);
    Route::get('/vendor/pharmacy/details', [PharmacyController::class, 'details']);
    Route::put('/vendor/pharmacy', [PharmacyController::class, 'update']);

    Route::get('/vendor/pharmacy/details', [PharmacyController::class, 'details']);
    Route::post('/vendor/pharmacy/update-photo', [PharmacyController::class, 'updatePhoto']);

     Route::get('/vendor/profile', [PharmacyController::class, 'getProfile']);

    // (optional better one — full details)
    Route::get('/vendor/pharmacy-details', [PharmacyController::class, 'details']);

    
    // Prescriptions
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/vendor/prescriptions', [PrescriptionController::class, 'vendorInbox']);
    Route::post('/vendor/prescriptions/{id}/status', [PrescriptionController::class, 'updateStatus']);
    Route::get('/user/prescriptions', [PrescriptionController::class, 'userPrescriptions']);

    Route::post('/user/update-prescription', [PrescriptionController::class, 'updatePrescription']);



    Route::get('/user/orders', [OrderController::class, 'userOrders']);
    Route::post('/user/orders', [OrderController::class, 'store']);
    Route::get('/vendor/orders', [OrderController::class, 'vendorOrders']);
    Route::get('/vendor/analytics', [OrderController::class, 'vendorAnalytics']);
    Route::get('/vendor/orders/export', [OrderController::class, 'exportDeliveredPrescriptions']);
    Route::post('/user/orders/{id}/confirm', [OrderController::class, 'confirmDelivery']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/vendor/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/user/orders/{id}/cancel', [OrderController::class, 'cancel']);

    Route::post('/orders/{id}/reorder', [OrderController::class, 'reorder']);

    // ✅ THIS IS THE MISSING ROUTE
    Route::get('/vendor/orders/{id}', [OrderController::class,'show']);
    Route::get('/order-history', [OrderController::class, 'history']);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);


    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::put('/addresses/{id}/default', [AddressController::class,'setDefault']);

    Route::put('/addresses/{id}', [AddressController::class, 'update']);


    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);


    Route::put('/addresses/{id}/default', [AddressController::class,'setDefault']);




    Route::get('/patients', [PatientController::class,'index']);
    Route::post('/patients', [PatientController::class,'store']);
    Route::post('/patients/set-default/{id}', [PatientController::class,'setDefault']);

    
    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::put('/reminders/{id}', [ReminderController::class, 'update']);
    Route::put('/reminders/{id}/toggle', [ReminderController::class, 'toggle']);
    Route::delete('/reminders/{id}', [ReminderController::class, 'destroy']);
    Route::get('/reminders/{id}', [ReminderController::class, 'show']);


    
    Route::post('/help', [HelpController::class, 'store']);
    Route::get('/help', [HelpController::class, 'index']);


});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'is_admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/pharmacies/pending', [AdminPharmacyController::class, 'pending']);
        Route::post('/pharmacies/{id}/approve', [AdminPharmacyController::class, 'approve']);
        Route::post('/pharmacies/{id}/reject', [AdminPharmacyController::class, 'reject']);
});

/*
|--------------------------------------------------------------------------
| PUBLIC PHARMACY DISCOVERY
|--------------------------------------------------------------------------
*/

Route::get('/pharmacies', [PharmacyListingController::class, 'index']);




/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'is_admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/pharmacies/pending', [AdminPharmacyController::class, 'pending']);
        Route::post('/pharmacies/{id}/approve', [AdminPharmacyController::class, 'approve']);
        Route::post('/pharmacies/{id}/reject', [AdminPharmacyController::class, 'reject']);

        Route::get('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'index']);
        Route::post('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'store']);
        Route::post('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'update']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'destroy']);

        Route::get('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index']);
        Route::post('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'store']);
        Route::post('/services/{id}', [\App\Http\Controllers\Admin\ServiceController::class, 'update']);
        Route::delete('/services/{id}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroy']);

        Route::get('/contact-queries', [ContactQueryController::class, 'index']);
        Route::put('/contact-queries/{id}/read', [ContactQueryController::class, 'markRead']);
        Route::get('/contact-queries/unread-count', [ContactQueryController::class, 'unreadCount']);

        Route::get('/social-links', [\App\Http\Controllers\Admin\SocialLinkController::class, 'index']);
        Route::post('/social-links', [\App\Http\Controllers\Admin\SocialLinkController::class, 'store']);
        Route::put('/social-links/{id}', [\App\Http\Controllers\Admin\SocialLinkController::class, 'update']);
        Route::delete('/social-links/{id}', [\App\Http\Controllers\Admin\SocialLinkController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/


Route::get('/pharmacies', [PharmacyListingController::class, 'index']);
Route::get('/insights', [InsightController::class, 'index']);
Route::get('/site-content', [SiteContentController::class, 'site']);
Route::get('/service-page', [SiteContentController::class, 'servicesPage']);
Route::get('/social-links', [SocialLinkController::class, 'index']);
Route::get('/pages/{slug}', [\App\Http\Controllers\Api\PageController::class, 'show']);
Route::get('/faqs/{type}', [FaqController::class, 'index']);
Route::post('/contact-queries', [ContactQueryController::class, 'store']);



Route::get('/banners', [BannerController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);

Route::get('/home-services', [ServiceController::class, 'homeServices']);
Route::get('/page-services', [ServiceController::class, 'pageServices']);



Route::get('/app-settings', [AppSettingController::class, 'index']);
Route::get('/app-version', [AppVersionController::class, 'show']);
Route::get('/translations', [TranslationController::class, 'index']);



Route::get('/pages/{slug}', [PageController::class, 'show']);


Route::post('/pharmacy/toggle-status', [PharmacyController::class, 'toggleStatus']);
Route::post('/vendor/pharmacy/toggle-status', [PharmacyController::class, 'toggleStatus']);
Route::post('/vendor/pharmacy/update', [PharmacyController::class, 'update']);
