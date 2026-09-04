<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\Search\SearchController;
use App\Http\Controllers\Frontend\SmartPage\PublicSmartPageController;
use App\Http\Controllers\Admin\Prospect\ProspectController;
use App\Http\Controllers\Admin\Prospect\SmartPageController;
use App\Http\Controllers\Admin\Prospect\SmartDashboardController;
use App\Http\Controllers\Admin\Prospect\IntentSettingController;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');

/*
|--------------------------------------------------------------------------
| Smart Links MVP
|--------------------------------------------------------------------------
| Public Smart Page + the prospect/intent workflow described in the scope PDF.
*/

Route::get('/s/{slug}', [PublicSmartPageController::class, 'show'])->name('smart.page');
Route::post('/s/{slug}/track', [PublicSmartPageController::class, 'track'])
    ->middleware('throttle:240,1')->name('smart.track');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,salesperson', 'throttle:200,1'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('prospects', [ProspectController::class, 'index'])->name('prospects.index');
    Route::get('prospects/create', [ProspectController::class, 'create'])->name('prospects.create');
    Route::post('prospects', [ProspectController::class, 'store'])->name('prospects.store');
    Route::get('prospects/{prospect}', [ProspectController::class, 'show'])->name('prospects.show');
    Route::get('prospects/{prospect}/edit', [ProspectController::class, 'edit'])->name('prospects.edit');
    Route::put('prospects/{prospect}', [ProspectController::class, 'update'])->name('prospects.update');
    Route::delete('prospects/{prospect}', [ProspectController::class, 'destroy'])->name('prospects.destroy');
    Route::put('prospects/{prospect}/status', [ProspectController::class, 'updateStatus'])->name('prospects.status');
    Route::post('prospects/{prospect}/regenerate-link', [ProspectController::class, 'regenerateLink'])->name('prospects.regenerate');

    Route::get('prospects/{prospect}/page', [SmartPageController::class, 'edit'])->name('prospects.page.edit');
    Route::put('prospects/{prospect}/page', [SmartPageController::class, 'update'])->name('prospects.page.update');
    Route::post('prospects/{prospect}/page/portfolio', [SmartPageController::class, 'uploadPortfolio'])->name('prospects.page.portfolio.store');
    Route::delete('prospects/{prospect}/page/portfolio', [SmartPageController::class, 'deletePortfolio'])->name('prospects.page.portfolio.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::get('smart-dashboard', [SmartDashboardController::class, 'index'])->name('smart.dashboard');
        Route::get('intent-settings', [IntentSettingController::class, 'index'])->name('intent.settings');
        Route::put('intent-settings', [IntentSettingController::class, 'update'])->name('intent.settings.update');

        Route::resource('users', UserController::class)->except('show');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
