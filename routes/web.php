<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Client\ClientController;
use App\Http\Controllers\Admin\Lead\LeadController;
use App\Http\Controllers\Admin\FollowUp\FollowUpController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Presentation\PresentationController;
use App\Http\Controllers\Admin\Analytics\AnalyticsController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\Search\SearchController;
use App\Http\Controllers\Frontend\Presentation\PublicPresentationController;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');

Route::get('/p/{token}', [PublicPresentationController::class, 'show'])->name('presentation.public');
Route::post('/p/{token}/track', [PublicPresentationController::class, 'track'])->middleware('throttle:240,1')->name('presentation.track');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,admin,sales_manager,salesperson'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/live-visitors', [DashboardController::class, 'live'])->name('live');

    Route::resource('clients', ClientController::class)->except('show');
    Route::post('clients/import', [ClientController::class, 'import'])->name('clients.import');
    Route::get('clients-export', [ClientController::class, 'export'])->name('clients.export');

    Route::resource('leads', LeadController::class)->except('show');
    Route::post('leads/import', [LeadController::class, 'import'])->name('leads.import');
    Route::get('leads-export', [LeadController::class, 'export'])->name('leads.export');

    Route::resource('followups', FollowUpController::class)->except('show');
    Route::post('followups/import', [FollowUpController::class, 'import'])->name('followups.import');
    Route::get('followups-export', [FollowUpController::class, 'export'])->name('followups.export');

    Route::resource('presentations', PresentationController::class)->except('show');
    Route::post('presentations/import', [PresentationController::class, 'import'])->name('presentations.import');
    Route::get('presentations-export', [PresentationController::class, 'export'])->name('presentations.export');
    Route::put('presentations/{presentation}/sections', [PresentationController::class, 'updateSections'])->name('presentations.sections');
    Route::post('presentations/{presentation}/regenerate-token', [PresentationController::class, 'regenerateToken'])->name('presentations.regenerate-token');

    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics-export/all', [AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('analytics/{presentation}', [AnalyticsController::class, 'show'])->name('analytics.show');

    Route::middleware('role:super_admin,admin,sales_manager')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        Route::get('products-export', [ProductController::class, 'export'])->name('products.export');
        Route::post('products/{product}/features', [ProductController::class, 'addFeature'])->name('products.features.store');
        Route::delete('products/{product}/features/{feature}', [ProductController::class, 'deleteFeature'])->name('products.features.destroy');
        Route::post('products/{product}/demos', [ProductController::class, 'addDemo'])->name('products.demos.store');
        Route::delete('products/{product}/demos/{demo}', [ProductController::class, 'deleteDemo'])->name('products.demos.destroy');
        Route::post('products/{product}/media', [ProductController::class, 'addMedia'])->name('products.media.store');
        Route::delete('products/{product}/media/{media}', [ProductController::class, 'deleteMedia'])->name('products.media.destroy');
    });

    Route::middleware('role:super_admin,admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
