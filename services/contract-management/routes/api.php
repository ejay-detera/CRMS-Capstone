<?php

use App\Http\Controllers\ContractController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    Route::get('/contracts', [ContractController::class, 'index']);

    // TODO: restore permission middleware once create-contracts/view-contracts are seeded in auth-service
    Route::post('/contracts', [ContractController::class, 'store']);
    Route::get('/contracts/{id}',  [ContractController::class, 'show']);
    Route::put('/contracts/{id}',  [ContractController::class, 'update']);

    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:manage-users');

});
