<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\LookupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {

    Route::get('/lookups/{type}', [LookupController::class, 'show']);
    
    Route::get('/dashboard',           [ContractController::class, 'dashboardSummary']);
    Route::get('/contracts',          [ContractController::class, 'index']);
    Route::get('/contract-requests',  [ContractController::class, 'indexRequests']);


    Route::post('/contracts', [ContractController::class, 'store'])
        ->middleware('role:Manager,Sales');

    Route::get('/contracts/{id}',    [ContractController::class, 'show']);
    Route::put('/contracts/{id}',    [ContractController::class, 'update']);
    Route::patch('/contracts/{id}/status', [ContractController::class, 'updateStatus'])
        ->middleware('role:Manager,Admin');

    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:manage-users');

});
