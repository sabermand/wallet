<?php

use App\Http\Controllers\Api\V1\TransactionController;

Route::prefix('v1')->group(function () {
    // auth routes
   
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/transactions/transfer', [TransactionController::class, 'transfer'])
            ->middleware('throttle:transfer');
    });

});
