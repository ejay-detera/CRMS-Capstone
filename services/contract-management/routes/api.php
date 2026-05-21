<?php

use App\Http\Controllers\ContractController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    Route::get('/contracts', function (Request $request) {
        return response()->json([
            'message' => 'Contract list retrieved',
            'data' => [
                ['id' => 1, 'title' => 'Service Level Agreement - 2024'],
                ['id' => 2, 'title' => 'NDA - Tech Corp']
            ]
        ]);
    })->middleware('permission:view-contracts');

    // TODO: restore ->middleware('permission:create-contracts') once the permission is seeded in auth-service
    Route::post('/contracts', [ContractController::class, 'store']);

    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:manage-users');

});
