<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Middleware\InitializeTenancyFromUser;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->get('/login', fn () => Inertia::render('auth/login'))
    ->name('login');

Route::middleware(['guest', 'throttle:login'])
    ->post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store');

Route::middleware('auth')
    ->post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware(['auth', InitializeTenancyFromUser::class])->group(function () {
    Route::get('/settings', [UserSettingsController::class, 'edit'])
        ->name('settings.edit');
    Route::get('/settings/avatar', [UserSettingsController::class, 'avatar'])
        ->name('settings.avatar.show');
    Route::post('/settings/avatar', [UserSettingsController::class, 'updateAvatar'])
        ->name('settings.avatar.update');
    Route::delete('/settings/avatar', [UserSettingsController::class, 'destroyAvatar'])
        ->name('settings.avatar.destroy');

    Route::resource('transactions', TransactionController::class)
        ->whereNumber('transaction');
    Route::get('/transactions/{transaction}/document', [TransactionController::class, 'document'])
        ->whereNumber('transaction')
        ->name('transactions.document');
});
