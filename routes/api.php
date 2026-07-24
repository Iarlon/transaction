<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\InitializeTenancyFromUser;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::middleware('throttle:login')
        ->post('/login', [AuthController::class, 'store'])
        ->name('login');

    Route::middleware(['auth:sanctum', InitializeTenancyFromUser::class])->group(function () {
        Route::get('/user', [AuthController::class, 'show'])->name('user.show');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::apiResource('transactions', TransactionController::class)
            ->whereNumber('transaction');
        Route::get('/transactions/{transaction}/document', [TransactionController::class, 'document'])
            ->whereNumber('transaction')
            ->name('transactions.document');
    });
});
