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
use App\Http\Controllers\Admin\Prospect\SmartTemplateController;
use App\Http\Controllers\Admin\Prospect\IntentSettingController;
use App\Http\Controllers\Admin\Setting\InstagramSettingController;
use App\Http\Controllers\Api\InstagramWorkerController;

/*
 * Called by the local worker PC (local_pc_instagram_script), not by a browser: a shared
 * secret stands in for a login, and the CSRF token a session would carry does not exist.
 */
Route::prefix('api/instagram')->middleware('throttle:120,1')->group(function () {
    Route::get('session', [InstagramWorkerController::class, 'session'])->name('instagram.worker.session');
    Route::get('pending', [InstagramWorkerController::class, 'pending'])->name('instagram.worker.pending');
    Route::post('result', [InstagramWorkerController::class, 'result'])->name('instagram.worker.result');
});

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');

/*
|--------------------------------------------------------------------------
| Smart Links MVP
|--------------------------------------------------------------------------
| Public Smart Page + the prospect/intent workflow described in the scope PDF.
*/

Route::get('/s/{slug}', [PublicSmartPageController::class, 'legacy'])->name('smart.page.legacy');

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
    Route::post('prospects/{prospect}/audit', [ProspectController::class, 'runAudit'])->name('prospects.audit');
    Route::post('prospects/{prospect}/instagram-audit', [ProspectController::class, 'runInstagramAudit'])->name('prospects.instagram');

    Route::get('prospects/{prospect}/page', [SmartPageController::class, 'edit'])->name('prospects.page.edit');
    Route::put('prospects/{prospect}/page', [SmartPageController::class, 'update'])->name('prospects.page.update');
    Route::post('prospects/{prospect}/page/portfolio', [SmartPageController::class, 'uploadPortfolio'])->name('prospects.page.portfolio.store');
    Route::delete('prospects/{prospect}/page/portfolio', [SmartPageController::class, 'deletePortfolio'])->name('prospects.page.portfolio.destroy');

    Route::get('smart-templates', [SmartTemplateController::class, 'index'])->name('templates.index');
    Route::get('smart-templates/{template}/preview', [SmartTemplateController::class, 'preview'])->name('templates.preview');

    Route::middleware('role:admin')->group(function () {
        Route::get('smart-dashboard', [SmartDashboardController::class, 'index'])->name('smart.dashboard');
        Route::get('intent-settings', [IntentSettingController::class, 'index'])->name('intent.settings');
        Route::put('intent-settings', [IntentSettingController::class, 'update'])->name('intent.settings.update');

        Route::resource('users', UserController::class)->except('show');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('instagram-settings', [InstagramSettingController::class, 'index'])->name('instagram.settings');
        Route::put('instagram-settings', [InstagramSettingController::class, 'update'])->name('instagram.settings.update');
    });
});

/*
|--------------------------------------------------------------------------
| Public Smart Page — /{link}/{name}
|--------------------------------------------------------------------------
| Registered LAST and on purpose. A two-segment route at the root would otherwise
| swallow every other URL, so it sits after the admin group and the id segment is
| constrained to digits: "admin", "login" and friends can never match it. Only the
| id identifies the page; {name} is cosmetic and a stale one is redirected, which
| is what lets two prospects share a business name without sharing a URL.
*/
Route::get('/{link}/{name}', [PublicSmartPageController::class, 'show'])
    ->whereNumber('link')->name('smart.page');

Route::post('/{link}/track', [PublicSmartPageController::class, 'track'])
    ->whereNumber('link')->middleware('throttle:240,1')->name('smart.track');
