<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\PrescriptionController;
// use App\Http\Controllers\Admin\OrderController;

// routes/web.php
use App\Http\Controllers\Admin\HelpAdminController;

use App\Http\Controllers\Admin\ManageBannerController;
use App\Http\Controllers\Admin\ManageServiceController;
use App\Http\Controllers\Admin\ManagePageController;
use App\Http\Controllers\Admin\ManageFaqController;
use App\Http\Controllers\Admin\ManageContactQueryController;

use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\ManageAppSettingController;
use App\Http\Controllers\Admin\PushNotificationController;
use App\Http\Controllers\Admin\ManageTranslationController;


// Private prescription images. Same URL shape as the old public files, so the
// live apps keep working, but the link must carry a valid, unexpired signature.
Route::get('/storage/prescriptions/{file}', [\App\Http\Controllers\PrescriptionFileController::class, 'show'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->middleware('signed:relative')
    ->name('prescription-files.show');

/*
|--------------------------------------------------------------------------
| ADMIN AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {

    // Admin Login
    Route::get('/login',[AdminAuthController::class,'loginForm'])->name('login');
    Route::post('/login',[AdminAuthController::class,'login']);

});


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function(){

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard',[DashboardController::class,'index'])->name('admin.dashboard');
    

    Route::get('/export/orders', [DashboardController::class, 'exportOrders']);
    Route::get('/export/dashboard', [DashboardController::class, 'exportDashboard'])->name('export.dashboard');
    



    /*
    |--------------------------------------------------------------------------
    | ADMIN LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post('/logout',[AdminAuthController::class,'logout'])->name('admin.logout');


    /*
    |--------------------------------------------------------------------------
    | USERS MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get('/users',[UserController::class,'index']);
    Route::get('/users/{id}',[UserController::class,'show']);

    Route::post('/users/{id}/block',[UserController::class,'block']);
    Route::post('/users/{id}/unblock',[UserController::class,'unblock']);

    Route::get('/users/{id}/edit',[UserController::class,'edit']);
    Route::post('/users/{id}/update',[UserController::class,'update']);


    /*
    |--------------------------------------------------------------------------
    | VENDORS MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get('/vendors',[VendorController::class,'index']);
    Route::get('/vendors/{id}',[VendorController::class,'show']);

    // Vendor Orders
    Route::get('/vendors/{id}/orders',[VendorController::class,'orders']);

    // Vendor Prescriptions
    Route::get('/vendors/{id}/prescriptions',[VendorController::class,'prescriptions']);

    // Vendor Block / Unblock
    Route::post('/vendors/{id}/block',[VendorController::class,'block']);
    Route::post('/vendors/{id}/unblock',[VendorController::class,'unblock']);

    Route::post('/pharmacy/update-status/{id}', [VendorController::class, 'updatePharmacyStatus'])->name('pharmacy.updateStatus');


    /*
    |--------------------------------------------------------------------------
    | PHARMACY VERIFICATION
    |--------------------------------------------------------------------------
    */

    Route::get('/pharmacies/pending',[VendorController::class,'pendingPharmacies']);

    Route::post('/pharmacies/{id}/approve',[VendorController::class,'approvePharmacy']);
    Route::post('/pharmacies/{id}/reject',[VendorController::class,'rejectPharmacy']);


    /*
    |--------------------------------------------------------------------------
    | PRESCRIPTIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/prescriptions',[PrescriptionController::class,'index']);
    Route::get('/prescriptions/{id}',[PrescriptionController::class,'show']);

    Route::post('/admin/pharmacies/{id}/approve', [VendorController::class,'approvePharmacy']);
    //Route::post('/admin/pharmacies/{id}/reject', [VendorController::class,'rejectPharmacy']);

    Route::get('/admin/pharmacies/history', [VendorController::class,'pharmacyHistory']);

    /*
    |--------------------------------------------------------------------------
    | ORDERS MANAGEMENT
    |--------------------------------------------------------------------------
    */

   // 🔔 Help Requests
    Route::get('/help-requests', [HelpAdminController::class, 'index'])->name('admin.help');

    // ℹ️ About
    // Route::view('/about', 'admin.pages.about')->name('admin.about');

    // // 📄 Terms
    // Route::view('/terms', 'admin.pages.terms')->name('admin.terms');

    // // 🔐 Privacy
    // Route::view('/privacy', 'admin.pages.privacy')->name('admin.privacy');





    /*
    |--------------------------------------------------------------------------
    | BANNERS
    |--------------------------------------------------------------------------
    */

    Route::get('/banners', [ManageBannerController::class, 'index']);
    Route::post('/banners', [ManageBannerController::class, 'store']);
    Route::post('/banners/{id}/update', [ManageBannerController::class, 'update']);
    Route::post('/banners/{id}/delete', [ManageBannerController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | SERVICES
    |--------------------------------------------------------------------------
    */

    Route::get('/services', [ManageServiceController::class, 'index']);
    Route::post('/services', [ManageServiceController::class, 'store']);
    Route::post('/services/{id}/update', [ManageServiceController::class, 'update']);
    Route::post('/services/{id}/delete', [ManageServiceController::class, 'destroy']);



    
/*
|--------------------------------------------------------------------------
| SOCIAL LINKS
|--------------------------------------------------------------------------
*/

Route::get('/social-links', [SocialLinkController::class, 'index'])
    ->name('social-links.index');

Route::get('/social-links/create', [SocialLinkController::class, 'create'])
    ->name('social-links.create');

Route::post('/social-links/store', [SocialLinkController::class, 'store'])
    ->name('social-links.store');

Route::get('/social-links/{id}/edit', [SocialLinkController::class, 'edit'])
    ->name('social-links.edit');

Route::post('/social-links/{id}/update', [SocialLinkController::class, 'update'])
    ->name('social-links.update');

Route::post('/social-links/{id}/delete', [SocialLinkController::class, 'destroy'])
    ->name('social-links.delete');


    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    Route::get('/pages', [ManagePageController::class, 'index'])->name('pages.index');

    Route::get('/pages/create', [ManagePageController::class, 'create'])->name('pages.create');

    Route::post('/pages/store', [ManagePageController::class, 'store'])->name('pages.store');

    Route::get('/pages/{id}/edit', [ManagePageController::class, 'edit'])->name('pages.edit');

    Route::post('/pages/{id}/update', [ManagePageController::class, 'update'])->name('pages.update');

    Route::post('/pages/{id}/delete', [ManagePageController::class, 'destroy'])->name('pages.delete');

    /*
    |--------------------------------------------------------------------------
    | FAQS
    |--------------------------------------------------------------------------
    */

    Route::get('/faqs', [ManageFaqController::class, 'index'])->name('faqs.index');
    Route::get('/faqs/create', [ManageFaqController::class, 'create'])->name('faqs.create');
    Route::post('/faqs/store', [ManageFaqController::class, 'store'])->name('faqs.store');
    Route::get('/faqs/{id}/edit', [ManageFaqController::class, 'edit'])->name('faqs.edit');
    Route::post('/faqs/{id}/update', [ManageFaqController::class, 'update'])->name('faqs.update');
    Route::post('/faqs/{id}/delete', [ManageFaqController::class, 'destroy'])->name('faqs.delete');

    Route::get('/translations', [ManageTranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations', [ManageTranslationController::class, 'store'])->name('translations.store');
    Route::post('/translations/{translation}', [ManageTranslationController::class, 'update'])->name('translations.update');

    /*
    |--------------------------------------------------------------------------
    | ORDERS MANAGEMENT
    |--------------------------------------------------------------------------
    */

   // Route::get('/orders',[OrderController::class,'index']);
    //Route::get('/orders/{id}',[OrderController::class,'show']);

    /*
    |--------------------------------------------------------------------------
    | CONTACT QUERIES
    |--------------------------------------------------------------------------
    */

    Route::get('/contact-queries', [ManageContactQueryController::class, 'index']);
    Route::post('/contact-queries/{id}/read', [ManageContactQueryController::class, 'markRead']);
    Route::post('/contact-queries/{id}/delete', [ManageContactQueryController::class, 'destroy']);
    Route::post('/contact-queries/delete-selected', [ManageContactQueryController::class, 'destroySelected']);
    Route::post('/contact-queries/delete-all', [ManageContactQueryController::class, 'destroyAll']);

    Route::get('/notifications', [PushNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications', [PushNotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/{notification}/resend', [PushNotificationController::class, 'resend'])->name('notifications.resend');
    Route::post('/notifications/{notification}/delete', [PushNotificationController::class, 'destroy'])->name('notifications.delete');

    Route::get('/app-settings', [ManageAppSettingController::class, 'index']);
    Route::post('/app-settings/update', [ManageAppSettingController::class, 'update']);

});

Route::view('/account-deletion', 'account-deletion')->name('account-deletion');

// React Landing Page (Fallback Route)
    Route::get('/{any}', function () {
        return file_get_contents(public_path('index.html'));
    })->where('any', '^(?!admin|api).*$');
