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

    // Suppliers Routes
    Route::get('/suppliers/search', [\App\Http\Controllers\SupplierController::class, 'search'])
        ->middleware('permission:view-suppliers');
    Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])
        ->middleware('permission:view-suppliers');
    Route::get('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'show'])
        ->middleware('permission:view-suppliers');
    Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])
        ->middleware('permission:manage-suppliers');
    Route::put('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])
        ->middleware('permission:manage-suppliers');
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])
        ->middleware('permission:manage-suppliers');

    // Business Partners Routes
    Route::get('/partners/search', [\App\Http\Controllers\BusinessPartnerController::class, 'search'])
        ->middleware('permission:view-partners');
    Route::get('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'index'])
        ->middleware('permission:view-partners');
    Route::get('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'show'])
        ->middleware('permission:view-partners');
    Route::post('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'store'])
        ->middleware('permission:manage-partners');
    Route::put('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'update'])
        ->middleware('permission:manage-partners');
    Route::delete('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'destroy'])
        ->middleware('permission:manage-partners');

    // Aggregated Audit Logs Route
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('permission:manage-users');
});

// Internal webhook for login/logout events from auth-service
Route::post('/internal/audit-event', [\App\Http\Controllers\InternalAuditController::class, 'receive']);
