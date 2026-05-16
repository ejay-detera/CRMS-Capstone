<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    // Example: Only users with 'view-vendors' permission can access this
    Route::get('/vendors', function (Request $request) {
        return response()->json([
            'message' => 'Vendor list retrieved successfully',
            'user' => $request->get('auth_user'),
            'data' => [
                ['id' => 1, 'name' => 'Global Logistics Inc.'],
                ['id' => 2, 'name' => 'Tech Solutions Corp.']
            ]
        ]);
    })->middleware('permission:view-vendors');

    // Example: Only users with 'manage-vendors' permission can create
    Route::post('/vendors', function (Request $request) {
        return response()->json([
            'message' => 'Vendor created successfully'
        ]);
    })->middleware('permission:manage-vendors');

    // Admin User Creation Proxy (Finance Only)
    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:manage-users');

});
