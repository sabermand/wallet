<?php

use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\Admin\StatisticsAdminController;
use App\Http\Controllers\Api\V1\Admin\TransactionAdminController;




Route::prefix('v1')->middleware('throttle:api')->group(function () {

    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        Route::post('/transactions/transfer', [TransactionController::class, 'transfer'])->middleware('throttle:transfer');

        Route::get('/wallets', [WalletController::class, 'index']);
        Route::post('/wallets', [WalletController::class, 'store']);

        Route::get('/wallets/{id}', [WalletController::class, 'show']);
        Route::get('/wallets/{id}/balance', [WalletController::class, 'balance']);
        Route::get('/wallets/{id}/transactions', [WalletController::class, 'transactions']);

        Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);
        Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw']);

        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
        Route::post('/transactions/{id}/refund', [TransactionController::class, 'refund']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'can:admin'])->group(function () {
        Route::get('/transactions/pending-review', [TransactionAdminController::class, 'pendingReview']);
        Route::post('/transactions/{id}/approve', [TransactionAdminController::class, 'approve']);
        Route::post('/transactions/{id}/reject', [TransactionAdminController::class, 'reject']);
        Route::get('/statistics', [StatisticsAdminController::class, 'show']);

    });

});
