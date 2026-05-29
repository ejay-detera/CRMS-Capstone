<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    // Example: Only users with 'crms.partners.view' permission can access this
    Route::get('/vendors', function (Request $request) {
        return response()->json([
            'message' => 'Vendor list retrieved successfully',
            'user' => $request->get('auth_user'),
            'data' => [
                ['id' => 1, 'name' => 'Global Logistics Inc.'],
                ['id' => 2, 'name' => 'Tech Solutions Corp.']
            ]
        ]);
    })->middleware('permission:crms.partners.view');

    // Example: Only users with 'crms.partners.create' permission can create
    Route::post('/vendors', function (Request $request) {
        return response()->json([
            'message' => 'Vendor created successfully'
        ]);
    })->middleware('permission:crms.partners.create');

    // Admin User Creation Proxy (Finance Only)
    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:crms.users.create');

    // Suppliers Routes
    Route::get('/suppliers/search', [\App\Http\Controllers\SupplierController::class, 'search'])
        ->middleware('permission:crms.partners.view');
    Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])
        ->middleware('permission:crms.partners.view');
    Route::get('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'show'])
        ->middleware('permission:crms.partners.view');
    Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])
        ->middleware('permission:crms.partners.create');
    Route::put('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])
        ->middleware('permission:crms.partners.edit');
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])
        ->middleware('permission:crms.partners.delete');

    // Business Partners Routes
    Route::get('/partners/search', [\App\Http\Controllers\BusinessPartnerController::class, 'search'])
        ->middleware('permission:crms.partners.view');
    Route::get('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'index'])
        ->middleware('permission:crms.partners.view');
    Route::get('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'show'])
        ->middleware('permission:crms.partners.view');
    Route::post('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'store'])
        ->middleware('permission:crms.partners.create');
    Route::put('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'update'])
        ->middleware('permission:crms.partners.edit');
    Route::delete('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'destroy'])
        ->middleware('permission:crms.partners.delete');

});
